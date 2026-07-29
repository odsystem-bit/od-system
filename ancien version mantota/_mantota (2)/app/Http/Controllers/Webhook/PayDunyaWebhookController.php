<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewSaleNotification;
use App\Services\Payment\PayDunyaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayDunyaWebhookController — Webhook unifie PayDunya.
 *
 * Gere :
 *  - Depot wallet (vendor recharge via PayDunya)
 *  - Paiement commande (Escrow via PayDunya)
 *
 * Securite :
 *  1. Verification du statut via l'API PayDunya Confirm.
 *  2. Idempotence : un webhook recu 2x ne credite qu'une seule fois.
 *  3. Transactions atomiques avec lockForUpdate() anti-race-condition.
 */
class PayDunyaWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // ── 1. Verification signature HMAC si configuree ──
        if (! PayDunyaService::verifyWebhookRequest($request)) {
            Log::warning('PayDunya unified webhook: signature HMAC invalide');
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $data = $request->all();

        // PayDunya envoie le statut dans data.status
        $status = $data['status'] ?? $data['response_code'] ?? null;

        if ($status !== 'completed' && $status !== '00') {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        // Verification supplementaire via l'API Confirm
        $invoiceToken = $data['invoice']['token'] ?? $data['token'] ?? null;

        // Routage : depot wallet OU paiement commande
        $customData = $data['custom_data'] ?? $data['metadata'] ?? [];

        if (! empty($customData['transaction_id'])) {
            return $this->handleDeposit((int) $customData['transaction_id'], $invoiceToken);
        }

        if (! empty($customData['order_id'])) {
            return $this->handleOrderPayment((int) $customData['order_id'], $invoiceToken);
        }

        Log::warning('PayDunya webhook: aucune transaction/commande identifiee', [
            'custom_data' => $customData,
        ]);

        return response()->json(['message' => 'No matching entity'], 200);
    }

    private function handleDeposit(int $transactionId, ?string $invoiceToken): JsonResponse
    {
        $transaction = Transaction::find($transactionId);

        if (! $transaction) {
            Log::warning('PayDunya webhook depot: transaction introuvable', [
                'transaction_id' => $transactionId,
            ]);
            return response()->json(['message' => 'Transaction not found'], 200);
        }

        if ($transaction->status === 'completed') {
            Log::info('PayDunya webhook depot: idempotence — deja traite', [
                'transaction_id' => $transactionId,
                'invoice_token'  => $invoiceToken,
            ]);
            return response()->json(['message' => 'Already processed'], 200);
        }

        // ── Verification API Confirm + montant ──
        if ($invoiceToken) {
            try {
                $confirm = PayDunyaService::confirmPayment($invoiceToken, (float) $transaction->amount_total);
                if (! $confirm['confirmed']) {
                    Log::warning('PayDunya webhook depot: confirmation echouee', [
                        'transaction_id' => $transactionId,
                        'reason'         => $confirm['message'],
                        'api_amount'     => $confirm['amount'],
                        'expected'       => $transaction->amount_total,
                    ]);
                    return response()->json(['message' => $confirm['message']], 200);
                }
            } catch (\Throwable $e) {
                Log::error('PayDunya webhook depot: erreur API confirm', [
                    'transaction_id' => $transactionId,
                    'error'          => $e->getMessage(),
                ]);
                return response()->json(['message' => 'Temporary error'], 500);
            }
        }

        DB::transaction(function () use ($transaction): void {
            $tx = Transaction::where('id', $transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tx->status === 'completed') {
                return;
            }

            $wallet = Wallet::where('user_id', $tx->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->balance = (float) $wallet->balance + (float) $tx->amount_target;
            $wallet->save();

            $tx->status = 'completed';
            $tx->save();
        });

        Log::info('PayDunya webhook: depot credite', [
            'transaction_id' => $transactionId,
            'user_id'        => $transaction->user_id,
            'amount'         => $transaction->amount_target,
        ]);

        \Illuminate\Support\Facades\Cache::forget('admin.dashboard');

        return response()->json(['message' => 'OK'], 200);
    }

    private function handleOrderPayment(int $orderId, ?string $invoiceToken): JsonResponse
    {
        $order = Order::with('product')->find($orderId);

        if (! $order) {
            Log::warning('PayDunya webhook order: commande introuvable', [
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Order not found'], 200);
        }

        // Idempotence : n'agir que si en attente de paiement
        if ($order->payment_status !== 'awaiting') {
            Log::info('PayDunya webhook order: idempotence — deja traite', [
                'order_id'      => $orderId,
                'invoice_token' => $invoiceToken,
            ]);
            return response()->json(['message' => 'Already processed'], 200);
        }

        // ── Verification API Confirm + montant ──
        if ($invoiceToken) {
            try {
                $confirm = PayDunyaService::confirmPayment($invoiceToken, (float) $order->amount_paid);
                if (! $confirm['confirmed']) {
                    Log::warning('PayDunya webhook order: confirmation echouee', [
                        'order_id'   => $orderId,
                        'reason'     => $confirm['message'],
                        'api_amount' => $confirm['amount'],
                        'expected'   => $order->amount_paid,
                    ]);
                    return response()->json(['message' => $confirm['message']], 200);
                }
            } catch (\Throwable $e) {
                Log::error('PayDunya webhook order: erreur API confirm', [
                    'order_id' => $orderId,
                    'error'    => $e->getMessage(),
                ]);
                return response()->json(['message' => 'Temporary error'], 500);
            }
        }

        DB::transaction(function () use ($order): void {
            $order = Order::with('product')
                ->where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->payment_status !== 'awaiting') {
                return;
            }

            // ── 1. Bloquer les gains vendeur en escrow ──
            $vendorWallet = Wallet::where('user_id', $order->vendor_id)
                ->lockForUpdate()
                ->firstOrFail();
            $vendorWallet->escrow_balance = (float) $vendorWallet->escrow_balance + (float) $order->vendor_earnings;
            $vendorWallet->save();

            // ── 2. Bloquer la commission créateur de contenu en escrow ──
            if ($order->influencer_id && (float) $order->commission_amount > 0) {
                $influencerWallet = Wallet::where('user_id', $order->influencer_id)
                    ->lockForUpdate()
                    ->first();
                if ($influencerWallet) {
                    $influencerWallet->escrow_balance = (float) $influencerWallet->escrow_balance + (float) $order->commission_amount;
                    $influencerWallet->save();
                }
            }

            // Stock deja decremente dans CheckoutController::store()

            // ── 3. Confirmer le paiement ──
            $order->update(['payment_status' => 'paid']);
        });

        $order->refresh();

        // Produit digital : livraison automatique
        \App\Http\Controllers\Public\CheckoutController::autoDeliverDigital($order);

        // ── Notifications vendeur + créateur de contenu ──
        if ($vendor = User::find($order->vendor_id)) {
            $vendor->notify(new NewSaleNotification($order, 'vendor'));
        }

        if ($order->influencer_id && $influencer = User::find($order->influencer_id)) {
            $influencer->notify(new NewSaleNotification($order, 'influencer'));
        }

        Log::info('PayDunya webhook: paiement commande confirme, escrow actif', [
            'order_id'       => $orderId,
            'vendor_id'      => $order->vendor_id,
            'vendor_earning' => $order->vendor_earnings,
        ]);

        \Illuminate\Support\Facades\Cache::forget('admin.dashboard');

        return response()->json(['message' => 'OK'], 200);
    }
}
