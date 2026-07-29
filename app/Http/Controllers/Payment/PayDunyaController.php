<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Payment\PayDunyaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * PayDunyaController — Gestion des paiements via la passerelle PayDunya.
 *
 * Responsabilites :
 *  - Initier un depot : calcul des frais, creation de la transaction locale,
 *    appel a l'API PayDunya (Checkout Invoice), redirection vers le lien de paiement.
 *  - Recevoir le webhook PayDunya : verification HMAC-SHA512,
 *    credit atomique du wallet avec lockForUpdate(), protection double-depense.
 */
class PayDunyaController extends Controller
{
    /**
     * Valide le montant, cree la transaction locale, appelle PayDunya
     * et redirige le vendor vers la page de paiement hebergee.
     */
    public function initiateDeposit(Request $request, PayDunyaService $service): SymfonyResponse
    {
        $validated = $request->validate([
            'amount_target' => ['required', 'numeric', 'min:' . (int) mantota_setting('min_deposit_amount', 1000)],
        ]);

        $user = auth()->user();

        // Creation de la transaction locale (statut : pending)
        $transaction = $service->createDepositTransaction($user, (float) $validated['amount_target']);
        $transaction->update(['payment_gateway' => 'paydunya']);

        // Chargement de la passerelle PayDunya depuis la base
        $gateway = Gateway::where('slug', 'paydunya')->where('is_active', true)->firstOrFail();

        $apiBase = $gateway->environment === 'live'
            ? 'https://app.paydunya.com'
            : 'https://app.paydunya.com/sandbox-api';

        // Resolution des cles API : gateway table > config > .env
        $masterKey  = $gateway->public_key ?: (string) config('services.paydunya.master_key', '');
        $privateKey = $gateway->secret_key ?: (string) config('services.paydunya.private_key', '');
        $token      = mantota_setting('paydunya_token', '');
        if (! is_string($token) || $token === '') {
            $token = (string) config('services.paydunya.token', '');
        }

        // Appel API PayDunya — creation de la facture Checkout
        $response = Http::withHeaders([
            'PAYDUNYA-MASTER-KEY'  => $masterKey,
            'PAYDUNYA-PRIVATE-KEY' => $privateKey,
            'PAYDUNYA-TOKEN'       => $token,
            'Content-Type'         => 'application/json',
        ])->timeout(15)->post("{$apiBase}/api/v1/checkout-invoice/create", [
            'invoice' => [
                'total_amount' => (int) $transaction->amount_total,
                'description'  => 'Recharge MANTOTA',
            ],
            'store' => [
                'name' => (string) mantota_setting('company_name', 'MANTOTA'),
            ],
            'custom_data' => [
                'transaction_id' => $transaction->id,
            ],
            'actions' => [
                'callback_url' => route('webhooks.paydunya'),
                'return_url'   => route('vendor.deposit.callback'),
                'cancel_url'   => route('vendor.dashboard'),
            ],
        ]);

        if ($response->failed() || $response->json('response_code') !== '00') {
            Log::error('PayDunya API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'txn_id' => $transaction->id,
            ]);

            $transaction->update(['status' => 'failed']);

            return back()->withErrors([
                'payment' => 'Erreur lors de la connexion au service de paiement PayDunya. Veuillez reessayer.',
            ]);
        }

        $paymentUrl = $response->json('response_text');

        if (! $paymentUrl) {
            Log::error('PayDunya: no payment URL in response', [
                'body'   => $response->body(),
                'txn_id' => $transaction->id,
            ]);

            $transaction->update(['status' => 'failed']);

            return back()->withErrors([
                'payment' => 'Lien de paiement introuvable dans la reponse PayDunya.',
            ]);
        }

        // Sauvegarder le token PayDunya sur la transaction pour la verification
        $pdyToken = $response->json('token', uniqid('', true));
        $transaction->update([
            'reference'   => 'DEP-PDY-' . $pdyToken,
            'gateway_ref' => $pdyToken,
        ]);

        // ── Redirection ou réponse JSON selon le type de requête ──
        if ($request->wantsJson()) {
            return response()->json([
                'payment_url'    => $paymentUrl,
                'transaction_id' => $transaction->id,
            ]);
        }

        return Inertia::location($paymentUrl);
    }

    /**
     * Webhook PayDunya (callback serveur).
     *
     * PayDunya envoie un POST avec les donnees de la transaction.
     * On verifie le statut en rappelant l'API PayDunya (Confirm endpoint).
     */
    public function webhook(Request $request, PayDunyaService $service): SymfonyResponse
    {
        // ── 1. Verification signature HMAC si configuree ──
        if (! PayDunyaService::verifyWebhookRequest($request)) {
            Log::warning('PayDunya webhook: signature HMAC invalide');
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $data = $request->all();

        // PayDunya envoie les donnees directement dans le body
        $status = $data['status'] ?? $data['response_code'] ?? null;

        // Verifier si le paiement est complete
        if ($status !== 'completed' && $status !== '00') {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        // Identification de la transaction locale via custom_data
        $transactionId = $data['custom_data']['transaction_id']
            ?? $data['metadata']['transaction_id']
            ?? null;

        if (! $transactionId) {
            Log::warning('PayDunya webhook: transaction_id manquant');
            return response()->json(['message' => 'Missing transaction_id'], 200);
        }

        $transaction = Transaction::find($transactionId);

        if (! $transaction) {
            Log::warning('PayDunya webhook: transaction introuvable', [
                'transaction_id' => $transactionId,
            ]);
            return response()->json(['message' => 'Transaction not found'], 200);
        }

        // Protection double-depense
        if ($transaction->status === 'completed') {
            Log::info('PayDunya webhook depot: idempotence — deja traite', [
                'transaction_id' => $transactionId,
            ]);
            return response()->json(['message' => 'Already processed'], 200);
        }

        // ── 2. Verification via API Confirm + controle du montant ──
        $invoiceToken = $data['invoice']['token'] ?? $data['token'] ?? null;

        if ($invoiceToken) {
            try {
                $confirm = PayDunyaService::confirmPayment($invoiceToken, (float) $transaction->amount_total);

                if (! $confirm['confirmed']) {
                    Log::warning('PayDunya webhook: confirmation echouee', [
                        'transaction_id' => $transactionId,
                        'reason'         => $confirm['message'],
                        'api_amount'     => $confirm['amount'],
                        'expected'       => $transaction->amount_total,
                    ]);
                    return response()->json(['message' => $confirm['message']], 200);
                }
            } catch (\Throwable $e) {
                Log::error('PayDunya webhook: erreur API confirm', [
                    'transaction_id' => $transactionId,
                    'error'          => $e->getMessage(),
                ]);
                return response()->json(['message' => 'Temporary error'], 500);
            }
        }

        // Credit atomique du wallet
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
            'transaction_id' => $transaction->id,
            'user_id'        => $transaction->user_id,
            'amount'         => $transaction->amount_target,
        ]);

        \Illuminate\Support\Facades\Cache::forget('admin.dashboard');

        return response()->json(['message' => 'OK'], 200);
    }
}
