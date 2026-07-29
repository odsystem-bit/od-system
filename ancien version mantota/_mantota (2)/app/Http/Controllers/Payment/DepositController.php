<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Payment\FeexPayService;
use App\Services\Payment\GatewayResolver;
use App\Services\Payment\PayDunyaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * DepositController — Point d'entree unifie pour les depots.
 *
 * Passerelles actives : PayDunya et FeexPay uniquement.
 */
class DepositController extends Controller
{
    public function __invoke(Request $request): SymfonyResponse
    {
        $userCountry = auth()->user()->country ?? null;
        $activeGateway = GatewayResolver::resolve($userCountry);

        if (! $activeGateway) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Aucune passerelle de paiement active.'], 422);
            }
            return back()->withErrors([
                'payment' => 'Aucune passerelle de paiement n\'est active. Contactez l\'administration.',
            ]);
        }

        return match ($activeGateway->slug) {
            'paydunya' => app(PayDunyaController::class)->initiateDeposit($request, app(PayDunyaService::class)),
            'feexpay'  => $this->initiateDepositFeexPay($request, $activeGateway),
            default    => $request->wantsJson()
                ? response()->json(['message' => 'Passerelle non supportee.'], 422)
                : back()->withErrors(['payment' => 'Passerelle de paiement non supportee.']),
        };
    }

    /**
     * Verifie le statut d'une transaction de depot (polling cote client).
     */
    public function checkStatus(Request $request, Transaction $transaction): JsonResponse
    {
        if ((int) $transaction->user_id !== (int) auth()->id()) {
            return response()->json(['status' => 'not_found'], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['status' => $transaction->status]);
        }

        if ($transaction->gateway_ref) {
            $slug = $transaction->payment_gateway ?? 'paydunya';

            $credited = match ($slug) {
                'paydunya' => self::verifyAndCreditPayDunya($transaction),
                'feexpay'  => self::verifyAndCreditFeexPay($transaction),
                default    => false,
            };

            if ($credited) {
                return response()->json(['status' => 'completed']);
            }
        }

        return response()->json(['status' => $transaction->status]);
    }

    /**
     * Interroge l'API PayDunya Confirm pour le vrai statut d'une transaction.
     */
    public static function verifyAndCreditPayDunya(Transaction $transaction): bool
    {
        if ($transaction->status !== 'pending' || ! $transaction->gateway_ref) {
            return false;
        }

        try {
            $status = PayDunyaService::checkPaymentStatus($transaction->gateway_ref);

            if ($status !== 'completed') {
                if (in_array($status, ['cancelled', 'failed'], true)) {
                    $transaction->update(['status' => 'failed']);
                }
                return false;
            }

            self::creditWallet($transaction);
            return true;
        } catch (\Throwable $e) {
            Log::error('PayDunya deposit verification error', [
                'txn_id' => $transaction->id,
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ──────────────────────────────────────────────
    //  Depot FeexPay (USSD push → polling)
    // ──────────────────────────────────────────────

    private function initiateDepositFeexPay(Request $request, Gateway $gateway): SymfonyResponse
    {
        $validated = $request->validate([
            'amount_target' => ['required', 'numeric', 'min:' . (int) mantota_setting('min_deposit_amount', 1000)],
            'phone'         => ['nullable', 'string', 'min:8', 'max:20'],
        ]);

        $user = auth()->user();
        $service = app(FeexPayService::class);
        $transaction = $service->createDepositTransaction($user, (float) $validated['amount_target']);
        $transaction->update(['payment_gateway' => 'feexpay']);

        $phone   = $validated['phone'] ?? $user->phone ?? $user->whatsapp_number ?? '';
        $network = FeexPayService::detectNetwork($phone, $user->country ?? 'BJ');

        if (! $network || ! $phone) {
            $transaction->update(['status' => 'failed']);
            $msg = 'Numero de telephone invalide ou reseau non detecte. Veuillez mettre a jour votre profil.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withErrors(['payment' => $msg]);
        }

        $result = $service->initiateMobilePayment(
            (int) ceil((float) $transaction->amount_total),
            $phone,
            $network,
            $user->name ?? 'Client',
            $user->email ?? ''
        );

        if (! $result['success']) {
            $transaction->update(['status' => 'failed']);
            $msg = 'Erreur FeexPay: ' . ($result['message'] ?? 'impossible d\'initier le paiement USSD.');
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withErrors(['payment' => $msg]);
        }

        $transaction->update([
            'gateway_ref' => $result['reference'],
            'reference'   => 'DEP-FXP-' . $result['reference'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'transaction_id' => $transaction->id,
                'gateway'        => 'feexpay',
                // Pas de payment_url : le paiement USSD est envoye sur le telephone
            ]);
        }

        return redirect()->route('vendor.deposit.callback', ['transaction_id' => $transaction->id]);
    }

    // ──────────────────────────────────────────────
    //  Verification FeexPay pour depots
    // ──────────────────────────────────────────────

    public static function verifyAndCreditFeexPay(Transaction $transaction): bool
    {
        if ($transaction->status !== 'pending' || ! $transaction->gateway_ref) {
            return false;
        }

        try {
            $result = FeexPayService::checkPaymentStatus($transaction->gateway_ref);
            $status = $result['status'] ?? null;

            if ($status !== 'SUCCESSFUL') {
                if ($status === 'FAILED') {
                    $transaction->update(['status' => 'failed']);
                }
                return false;
            }

            self::creditWallet($transaction);
            return true;
        } catch (\Throwable $e) {
            Log::error('FeexPay deposit verification error', [
                'txn_id' => $transaction->id,
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ──────────────────────────────────────────────
    //  Credit wallet commun a toutes les passerelles
    // ──────────────────────────────────────────────

    private static function creditWallet(Transaction $transaction): void
    {
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

        Cache::forget('admin.dashboard');

        Log::info('Deposit credited', [
            'transaction_id' => $transaction->id,
            'user_id'        => $transaction->user_id,
            'gateway'        => $transaction->payment_gateway,
            'amount'         => $transaction->amount_target,
        ]);
    }
}
