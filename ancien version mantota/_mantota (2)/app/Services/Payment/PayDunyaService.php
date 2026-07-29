<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Gateway;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayDunyaService — Moteur financier PayDunya pour MANTOTA.
 *
 * Meme logique que FedaPayService mais avec les taux PayDunya :
 *  - Frais gateway : 2 % (vs 1.5 % FedaPay)
 *  - Markup MANTOTA : parametrable via settings
 */
final class PayDunyaService
{
    /** Frais PayDunya par defaut : 2.0 %. Configurable via Admin > Parametres. */
    private const float DEFAULT_FEE_PERCENT = 2.0;

    /** Marge MANTOTA par defaut sur les depots : 2.0 %. */
    private const float DEFAULT_MARKUP_PERCENT = 2.0;

    private function feePercent(): float
    {
        return (float) mantota_setting('paydunya_fee_percent', self::DEFAULT_FEE_PERCENT);
    }

    private function markupPercent(): float
    {
        return (float) mantota_setting('deposit_markup_percent', self::DEFAULT_MARKUP_PERCENT);
    }

    private function feeRate(): float
    {
        return $this->feePercent() / 100;
    }

    /**
     * Calcule la decomposition financiere d'un depot vendor via Reverse Calculation.
     */
    public function calculateDeposit(float $targetAmount): array
    {
        $mantotaMarkup = round($targetAmount * ($this->markupPercent() / 100), 2);
        $subtotal      = round($targetAmount + $mantotaMarkup, 2);
        $amountTotal   = round($subtotal / (1 - $this->feeRate()), 2);
        $gatewayFee    = round($amountTotal - $subtotal, 2);

        return [
            'target_amount'  => round($targetAmount, 2),
            'gateway_fee'    => $gatewayFee,
            'mantota_markup' => $mantotaMarkup,
            'amount_total'   => $amountTotal,
        ];
    }

    /**
     * Cree une transaction de depot en base de donnees (statut : pending).
     */
    public function createDepositTransaction(User $vendor, float $targetAmount): Transaction
    {
        $breakdown = $this->calculateDeposit($targetAmount);

        return Transaction::create([
            'user_id'        => $vendor->id,
            'type'           => 'deposit',
            'amount_target'  => $breakdown['target_amount'],
            'gateway_fee'    => $breakdown['gateway_fee'],
            'mantota_markup' => $breakdown['mantota_markup'],
            'amount_total'   => $breakdown['amount_total'],
            'status'         => 'pending',
            'reference'      => 'DEP-' . uniqid('', true),
            'description'    => 'Recharge compte via PayDunya',
        ]);
    }

    /**
     * Calcule la decomposition financiere d'un retrait créateur de contenu.
     */
    public function calculateWithdrawal(float $requestedAmount): array
    {
        $withdrawalRate     = mantota_setting('withdrawal_fee_percent', 20) / 100;
        $mantotaCommission  = round($requestedAmount * $withdrawalRate, 2);
        $afterCommission    = round($requestedAmount - $mantotaCommission, 2);
        $gatewayFee         = round($afterCommission * $this->feeRate(), 2);
        $netPayout          = round($afterCommission - $gatewayFee, 2);

        return [
            'requested_amount'   => round($requestedAmount, 2),
            'mantota_commission' => $mantotaCommission,
            'gateway_fee'        => $gatewayFee,
            'net_payout'         => $netPayout,
        ];
    }

    /**
     * Verifie la signature HMAC-SHA512 d'un webhook PayDunya.
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $computedHash = hash_hmac('sha512', $payload, $secret);

        return hash_equals($computedHash, $signature);
    }

    /**
     * Confirme un paiement PayDunya via l'API Confirm ET verifie le montant.
     *
     * @param  string     $invoiceToken  Token PayDunya de la facture.
     * @param  float|null $expectedAmount Montant attendu (amount_total). Si fourni, on verifie la correspondance.
     * @return array{confirmed: bool, status: string|null, amount: float|null, message: string}
     */
    public static function confirmPayment(string $invoiceToken, ?float $expectedAmount = null): array
    {
        $config = self::resolveApiConfig();
        if (! $config) {
            return ['confirmed' => false, 'status' => null, 'amount' => null, 'message' => 'Gateway inactive'];
        }

        $response = Http::withHeaders(self::apiHeaders($config['masterKey'], $config['privateKey'], $config['token']))
            ->timeout(15)
            ->get("{$config['apiBase']}/api/v1/checkout-invoice/confirm/{$invoiceToken}");

        if ($response->failed()) {
            Log::error('PayDunya confirm API failed', [
                'token'  => $invoiceToken,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['confirmed' => false, 'status' => null, 'amount' => null, 'message' => 'API call failed'];
        }

        $apiStatus = $response->json('status');
        $apiAmount = (float) ($response->json('invoice.total_amount') ?? $response->json('total_amount') ?? 0);

        if ($apiStatus !== 'completed') {
            return ['confirmed' => false, 'status' => $apiStatus, 'amount' => $apiAmount, 'message' => 'Not completed'];
        }

        // Verification du montant (tolerance de 1 unite pour arrondi)
        if ($expectedAmount !== null && abs($apiAmount - $expectedAmount) > 1) {
            Log::critical('PayDunya AMOUNT MISMATCH', [
                'token'    => $invoiceToken,
                'expected' => $expectedAmount,
                'received' => $apiAmount,
            ]);
            return ['confirmed' => false, 'status' => $apiStatus, 'amount' => $apiAmount, 'message' => 'Amount mismatch'];
        }

        return ['confirmed' => true, 'status' => $apiStatus, 'amount' => $apiAmount, 'message' => 'OK'];
    }

    /**
     * Verifie la signature HMAC du webhook si un secret est configure.
     * Retourne true si pas de secret (pas bloquant), false si signature invalide.
     */
    public static function verifyWebhookRequest(Request $request): bool
    {
        $gateway = Gateway::where('slug', 'paydunya')->where('is_active', true)->first();

        $secret = $gateway?->webhook_secret
            ?: (string) config('services.paydunya.webhook_secret', '');

        // Si aucun secret configure, logger un warning mais laisser passer
        // (la verification Confirm API fait foi pour la securite)
        if (empty($secret)) {
            Log::warning('PayDunya webhook_secret non configure — verification HMAC desactivee. Configurez webhook_secret en production.');
            return true;
        }

        $signature = $request->header('X-PAYDUNYA-SIGNATURE')
            ?? $request->header('PAYDUNYA-SIGNATURE')
            ?? '';

        if (empty($signature)) {
            Log::warning('PayDunya webhook: signature absente alors que webhook_secret est configure');
            return false;
        }

        $payload = $request->getContent();
        $computed = hash_hmac('sha512', $payload, $secret);

        return hash_equals($computed, $signature);
    }

    // ──────────────────────────────────────────────
    //  Helpers API PayDunya (keys, base URL)
    // ──────────────────────────────────────────────

    /**
     * Retourne [apiBase, masterKey, privateKey, token] pour les appels API PayDunya.
     */
    public static function resolveApiConfig(): ?array
    {
        $gateway = Gateway::where('slug', 'paydunya')->where('is_active', true)->first();
        if (! $gateway) {
            return null;
        }

        $apiBase = $gateway->environment === 'live'
            ? 'https://app.paydunya.com'
            : 'https://app.paydunya.com/sandbox-api';

        $masterKey  = $gateway->public_key ?: (string) config('services.paydunya.master_key', '');
        $privateKey = $gateway->secret_key ?: (string) config('services.paydunya.private_key', '');
        $token      = mantota_setting('paydunya_token', '');
        if (! is_string($token) || $token === '') {
            $token = (string) config('services.paydunya.token', '');
        }

        return compact('apiBase', 'masterKey', 'privateKey', 'token');
    }

    /**
     * Construit les headers d'authentification PayDunya.
     */
    public static function apiHeaders(string $masterKey, string $privateKey, string $token): array
    {
        return [
            'PAYDUNYA-MASTER-KEY'  => $masterKey,
            'PAYDUNYA-PRIVATE-KEY' => $privateKey,
            'PAYDUNYA-TOKEN'       => $token,
        ];
    }

    // ──────────────────────────────────────────────
    //  Vérification de paiement via API Confirm
    // ──────────────────────────────────────────────

    /**
     * Vérifie le statut d'un paiement PayDunya via l'API Confirm.
     *
     * @param  string $invoiceToken  Le token retourné par PayDunya (stocké dans payment_gateway_ref).
     * @return string|null  'completed', 'pending', 'cancelled', etc. ou null si erreur.
     */
    public static function checkPaymentStatus(string $invoiceToken): ?string
    {
        $config = self::resolveApiConfig();
        if (! $config) {
            return null;
        }

        $response = Http::withHeaders(self::apiHeaders($config['masterKey'], $config['privateKey'], $config['token']))
            ->timeout(15)
            ->get("{$config['apiBase']}/api/v1/checkout-invoice/confirm/{$invoiceToken}");

        if ($response->failed()) {
            Log::error('PayDunya confirm API failed', [
                'token'  => $invoiceToken,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('status');
    }

    // ──────────────────────────────────────────────
    //  Remboursement via API PayDunya
    // ──────────────────────────────────────────────

    /**
     * Rembourse une commande via l'API PayDunya Refund.
     *
     * PayDunya API : POST /api/v1/refund/create
     * Body : { "invoice_token": "...", "amount": 100, "description": "..." }
     *
     * @param  Order  $order  La commande a rembourser.
     * @return array{success: bool, message: string}
     */
    public static function refundOrder(Order $order): array
    {
        if (! $order->payment_gateway_ref) {
            return ['success' => false, 'message' => 'Pas de reference passerelle — remboursement manuel requis.'];
        }

        try {
            $config = self::resolveApiConfig();
            if (! $config) {
                return ['success' => false, 'message' => 'Passerelle PayDunya non trouvee ou inactive.'];
            }

            $response = Http::withHeaders(self::apiHeaders($config['masterKey'], $config['privateKey'], $config['token']))
                ->timeout(15)
                ->post("{$config['apiBase']}/api/v1/refund/create", [
                    'invoice_token' => $order->payment_gateway_ref,
                    'amount'        => (int) $order->amount_paid,
                    'description'   => 'Remboursement commande ' . $order->reference,
                ]);

            if ($response->successful() && $response->json('response_code') === '00') {
                Log::info('PayDunya refund success', [
                    'order_id'    => $order->id,
                    'reference'   => $order->reference,
                    'gateway_ref' => $order->payment_gateway_ref,
                    'response'    => $response->json(),
                ]);
                return ['success' => true, 'message' => 'Remboursement PayDunya effectue.'];
            }

            Log::error('PayDunya refund FAILED', [
                'order_id'    => $order->id,
                'gateway_ref' => $order->payment_gateway_ref,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);

            return ['success' => false, 'message' => 'ATTENTION: Remboursement PayDunya echoue (HTTP ' . $response->status() . '). Remboursement manuel requis pour la commande ' . $order->reference . '.'];
        } catch (\Throwable $e) {
            Log::error('PayDunya refund exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erreur technique remboursement PayDunya : ' . $e->getMessage()];
        }
    }
}
