<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Gateway;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FeexPayService — Intégration FeexPay pour MANTOTA.
 *
 * API : https://api.feexpay.me
 * Docs : https://docs.feexpay.me
 *
 * Pays couverts : BJ, TG (principalement)
 * Opérateurs : MTN, MOOV, CELTIIS (Bénin) ; TMONEY, FLOOZ (Togo)
 * Payin : 1.7 %  |  Payout : 1.0 %
 *
 * Auth : shop_id + token (API key)
 *
 * Flow Mobile Money (USSD push) :
 *   1. POST /api/transactions/requesttopay/integration
 *      → USSD push sent to customer's phone
 *   2. GET /api/transactions/getrequesttopay/integration/{reference}
 *      → poll until status = SUCCESSFUL
 *
 * Flow Card :
 *   1. POST card endpoint → returns redirect URL
 *   2. Customer completes payment on hosted page
 *   3. Callback to return_url
 */
final class FeexPayService
{
    private const string API_BASE = 'https://api.feexpay.me';

    private const float DEFAULT_FEE_PERCENT = 1.7;
    private const float DEFAULT_MARKUP_PERCENT = 2.0;

    private function feePercent(): float
    {
        return (float) mantota_setting('feexpay_fee_percent', self::DEFAULT_FEE_PERCENT);
    }

    private function markupPercent(): float
    {
        return (float) mantota_setting('deposit_markup_percent', self::DEFAULT_MARKUP_PERCENT);
    }

    private function feeRate(): float
    {
        return $this->feePercent() / 100;
    }

    // ──────────────────────────────────────────────
    //  Résolution des credentials
    // ──────────────────────────────────────────────

    /**
     * Résout les credentials FeexPay.
     * public_key = shop_id, secret_key = token API
     */
    public static function resolveApiConfig(): array
    {
        $gateway = Gateway::where('slug', 'feexpay')->first();

        // shop_id
        $shopId = $gateway?->public_key ?? '';
        if (! is_string($shopId) || $shopId === '') {
            $shopId = (string) mantota_setting('feexpay_shop_id', '');
        }
        if (! $shopId) {
            $shopId = (string) config('services.feexpay.shop_id', '');
        }

        // token
        $token = $gateway?->secret_key ?? '';
        if (! is_string($token) || $token === '') {
            $token = (string) mantota_setting('feexpay_token', '');
        }
        if (! $token) {
            $token = (string) config('services.feexpay.token', '');
        }

        // callback_url
        $callbackUrl = route('webhook.feexpay');

        return [
            'shop_id'      => $shopId,
            'token'        => $token,
            'callback_url' => $callbackUrl,
            'api_base'     => self::API_BASE,
        ];
    }

    // ──────────────────────────────────────────────
    //  Normalisation du numéro de téléphone
    // ──────────────────────────────────────────────

    /**
     * Map indicatif pays → code ISO + longueur locale attendue.
     */
    private static array $countryDialCodes = [
        '229' => ['code' => 'BJ', 'local_len' => 10],
        '228' => ['code' => 'TG', 'local_len' => 8],
        '225' => ['code' => 'CI', 'local_len' => 10],
        '221' => ['code' => 'SN', 'local_len' => 9],
        '226' => ['code' => 'BF', 'local_len' => 8],
        '242' => ['code' => 'CG', 'local_len' => 9],
    ];

    /**
     * Normalise un numéro de téléphone au format international sans "+".
     *
     * Pays gérés : BJ (229), TG (228), CI (225), SN (221), BF (226), CG (242).
     *
     * @return array{phone: string, country: string}
     */
    public static function normalizePhone(string $phone, string $countryCode = 'BJ'): array
    {
        // Garder uniquement les chiffres
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Retirer le préfixe d'appel international 00
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Détecter l'indicatif pays en tête (3 chiffres)
        $local = $digits;
        foreach (self::$countryDialCodes as $dial => $info) {
            $dial = (string) $dial;
            if (str_starts_with($digits, $dial)) {
                $local = substr($digits, strlen($dial));
                $countryCode = $info['code'];
                break;
            }
        }

        // Retirer les zéros en tête du numéro local
        $local = ltrim($local, '0');

        $cc = strtoupper($countryCode);

        if ($cc === 'BJ') {
            // Bénin: migration vers 10 chiffres locaux (01 + ancien 8 chiffres)
            if (strlen($local) > 10) {
                $local = substr($local, -10);
            }
            if (strlen($local) === 8) {
                $local = '01' . $local;
            }
            if (strlen($local) === 9 && str_starts_with($local, '1')) {
                $local = '0' . $local;
            }
        } elseif ($cc === 'CI') {
            // Côte d'Ivoire: 10 chiffres locaux (migration 2021)
            if (strlen($local) > 10) {
                $local = substr($local, -10);
            }
        } elseif ($cc === 'SN' || $cc === 'CG') {
            // Sénégal / Congo Brazzaville: 9 chiffres locaux
            if (strlen($local) > 9) {
                $local = substr($local, -9);
            }
        } else {
            // Togo, Burkina Faso et autres: 8 chiffres locaux
            if (strlen($local) > 8) {
                $local = substr($local, -8);
            }
        }

        // Retrouver l'indicatif à partir du code pays
        $ccDial = '229'; // default BJ
        foreach (self::$countryDialCodes as $d => $info) {
            if ($info['code'] === $cc) {
                $ccDial = (string) $d;
                break;
            }
        }

        return [
            'phone'   => $ccDial . $local,
            'country' => $cc,
        ];
    }

    // ──────────────────────────────────────────────
    //  Détection automatique du réseau
    // ──────────────────────────────────────────────

    /**
     * Détecte le réseau mobile à partir du numéro de téléphone.
     *
     * Pays gérés : BJ, TG, CI, SN, BF, CG.
     *
     * @return string|null Network name accepté par FeexPay
     */
    public static function detectNetwork(string $phone, string $countryCode = 'BJ'): ?string
    {
        $normalized = self::normalizePhone($phone, $countryCode);
        $fullPhone  = $normalized['phone'];
        $cc         = $normalized['country'];

        // Retrouver la longueur de l'indicatif pays
        $dialLen = 3; // 229, 228, 225, 221, 226, 242 → tous 3 chiffres
        $local   = substr($fullPhone, $dialLen);

        // ── Bénin (BJ) ──
        if ($cc === 'BJ') {
            // Nouveau format: 01XXXXXXXX → préfixe opérateur = positions 2-3
            $prefix = (strlen($local) === 10 && str_starts_with($local, '01'))
                ? substr($local, 2, 2)
                : substr($local, 0, 2);

            $mtn     = ['51','52','53','54','61','62','66','67','69','90','91','96','97'];
            $moov    = ['42','60','64','68','87','89','95','98'];
            $celtiis = ['29','40','41','43','44','47','48','49','92','93'];

            if (in_array($prefix, $mtn, true))     return 'MTN';
            if (in_array($prefix, $moov, true))    return 'MOOV';
            if (in_array($prefix, $celtiis, true)) return 'CELTIIS BJ';
        }

        // ── Togo (TG) ──
        if ($cc === 'TG') {
            $prefix = substr($local, 0, 2);
            $togocom = ['70','73','90','91','92','93'];
            $moov    = ['79','96','97','98','99'];

            if (in_array($prefix, $togocom, true)) return 'TOGOCOM TG';
            if (in_array($prefix, $moov, true))    return 'MOOV TG';
        }

        // ── Côte d'Ivoire (CI) — 10 chiffres locaux ──
        if ($cc === 'CI') {
            $prefix = substr($local, 0, 2);
            $orange = ['07','08','09'];
            $mtn    = ['05','06'];
            $moov   = ['01','02','03'];

            if (in_array($prefix, $orange, true)) return 'ORANGE CI';
            if (in_array($prefix, $mtn, true))    return 'MTN CI';
            if (in_array($prefix, $moov, true))   return 'MOOV CI';
        }

        // ── Sénégal (SN) — 9 chiffres locaux ──
        if ($cc === 'SN') {
            $prefix = substr($local, 0, 2);
            $orange = ['77','78','70'];
            $free   = ['75','76'];

            if (in_array($prefix, $orange, true)) return 'ORANGE SN';
            if (in_array($prefix, $free, true))   return 'FREE SN';
        }

        // ── Burkina Faso (BF) — 8 chiffres locaux ──
        if ($cc === 'BF') {
            $prefix = substr($local, 0, 2);
            $orange = ['06','07'];
            $moov   = ['01','02','03','04','05'];

            if (in_array($prefix, $orange, true)) return 'ORANGE BF';
            if (in_array($prefix, $moov, true))   return 'MOOV BF';
        }

        // ── Congo Brazzaville (CG) — 9 chiffres locaux ──
        if ($cc === 'CG') {
            $prefix = substr($local, 0, 2);
            $mtn = ['04','05','06'];

            if (in_array($prefix, $mtn, true)) return 'MTN CG';
        }

        return null;
    }

    // ──────────────────────────────────────────────
    //  Initiation paiement Mobile Money (USSD push)
    // ──────────────────────────────────────────────

    /**
     * Initie un paiement mobile money via USSD push.
     *
     * Le client reçoit une invite USSD sur son téléphone et approuve le paiement.
     *
     * @return array{success: bool, reference: string|null, message: string}
     */
    public static function initiateMobilePayment(
        float  $amount,
        string $phoneNumber,
        string $network,
        string $fullName,
        string $email = ''
    ): array {
        $config = self::resolveApiConfig();

        // Normaliser le numéro au format international 229XXXXXXXX
        $normalized = self::normalizePhone($phoneNumber);
        $cleanPhone = $normalized['phone'];

        try {
            $response = Http::asForm()
                ->connectTimeout(5)
                ->timeout(30) // USSD push can be slow
                ->post(self::API_BASE . '/api/transactions/requesttopay/integration', [
                    'phoneNumber'  => $cleanPhone,
                    'amount'       => $amount,
                    'reseau'       => $network,
                    'token'        => $config['token'],
                    'shop'         => $config['shop_id'],
                    'first_name'   => $fullName,
                    'email'        => $email,
                    'callback_url' => $config['callback_url'],
                ]);

            if ($response->failed()) {
                Log::error('FeexPay initiate payment failed', [
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                    'phone'   => $cleanPhone,
                    'reseau'  => $network,
                ]);
                return ['success' => false, 'reference' => null, 'message' => 'Erreur FeexPay (HTTP ' . $response->status() . ')'];
            }

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === 'FAILED') {
                $operatorMsg = $data['response_operator']['description'][0] ?? 'Erreur operateur';
                Log::error('FeexPay FAILED from operator', [
                    'phone'    => $cleanPhone,
                    'network'  => $network,
                    'response' => $data,
                ]);
                return ['success' => false, 'reference' => null, 'message' => $operatorMsg];
            }

            $reference = $data['reference'] ?? null;
            if (! $reference) {
                return ['success' => false, 'reference' => null, 'message' => 'Reference FeexPay introuvable.'];
            }

            return ['success' => true, 'reference' => $reference, 'message' => 'USSD push envoye.'];
        } catch (\Throwable $e) {
            Log::error('FeexPay initiate payment exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'reference' => null, 'message' => 'Erreur technique: ' . $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────
    //  Vérification du statut de paiement
    // ──────────────────────────────────────────────

    /**
     * Vérifie le statut d'un paiement FeexPay.
     *
     * @return array{status: string|null, reason: string|null}
     */
    public static function checkPaymentStatus(string $reference): array
    {
        try {
            $config = self::resolveApiConfig();

            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->get(self::API_BASE . "/api/transactions/getrequesttopay/integration/{$reference}", [
                    'token' => $config['token'],
                    'shop'  => $config['shop_id'],
                ]);

            if ($response->failed()) {
                Log::warning('FeexPay status check failed', [
                    'reference' => $reference,
                    'status'    => $response->status(),
                ]);
                return ['status' => null, 'reason' => null];
            }

            return [
                'status' => $response->json('status'),  // SUCCESSFUL, PENDING, FAILED
                'reason' => $response->json('reason'),   // PAYER_NOT_FOUND, etc.
            ];
        } catch (\Throwable $e) {
            Log::error('FeexPay status check exception', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
            return ['status' => null, 'reason' => null];
        }
    }

    // ──────────────────────────────────────────────
    //  Calcul d'un dépôt — Reverse Calculation
    // ──────────────────────────────────────────────

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
            'description'    => 'Recharge compte via FeexPay',
        ]);
    }

    // ──────────────────────────────────────────────
    //  Calcul d'un retrait
    // ──────────────────────────────────────────────

    public function calculateWithdrawal(float $requestedAmount): array
    {
        $withdrawalRate    = mantota_setting('withdrawal_fee_percent', 20) / 100;
        $mantotaCommission = round($requestedAmount * $withdrawalRate, 2);
        $afterCommission   = round($requestedAmount - $mantotaCommission, 2);

        // Utiliser le taux payout (1.0%) et non payin (1.7%)
        $gateway = Gateway::where('slug', 'feexpay')->first();
        $payoutFeeRate = $gateway ? (float) $gateway->payout_fee / 100 : 0.01;
        $gatewayFee    = round($afterCommission * $payoutFeeRate, 2);
        $netPayout     = round($afterCommission - $gatewayFee, 2);

        return [
            'requested_amount'   => round($requestedAmount, 2),
            'mantota_commission' => $mantotaCommission,
            'gateway_fee'        => $gatewayFee,
            'net_payout'         => $netPayout,
        ];
    }

    // ──────────────────────────────────────────────
    //  Webhook signature (FeexPay callback)
    // ──────────────────────────────────────────────

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $computedHash = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computedHash, $signature);
    }

    // ──────────────────────────────────────────────
    //  Payout (retrait vers Mobile Money)
    // ──────────────────────────────────────────────

    /**
     * Envoie de l'argent vers un numero Mobile Money (retrait utilisateur).
     *
     * Utilise l'endpoint FeexPay Payouts API v2 (transfert sortant).
     * Endpoint : POST /api/payouts/public/transfer/global
     * Auth     : Bearer token dans le header Authorization.
     * Body     : JSON avec amount, phoneNumber, network, shop, motif.
     *
     * @param  string  $phone      Numero de telephone du beneficiaire
     * @param  int     $amount     Montant en FCFA
     * @param  string  $reference  Reference interne de la transaction
     * @param  string  $motif      Motif du transfert (obligatoire pour FeexPay)
     * @return array{success: bool, message: string, reference: string|null}
     */
    public static function payout(string $phone, int $amount, string $reference = '', string $motif = 'Retrait MANTOTA'): array
    {
        if (! $phone) {
            return ['success' => false, 'message' => 'Numero de telephone manquant pour le payout.', 'reference' => null];
        }

        try {
            $config = self::resolveApiConfig();
            $normalized = self::normalizePhone($phone);
            $network = self::detectNetwork($phone);

            if (! $network) {
                return ['success' => false, 'message' => 'Reseau non detecte pour le numero ' . $phone . '. Payout manuel requis.', 'reference' => null];
            }

            $response = Http::acceptJson()
                ->withToken($config['token'])
                ->connectTimeout(10)
                ->timeout(60)
                ->post(self::API_BASE . '/api/payouts/public/transfer/global', [
                    'phoneNumber' => $normalized['phone'],
                    'amount'      => $amount,
                    'network'     => $network,
                    'shop'        => $config['shop_id'],
                    'motif'       => $motif,
                ]);

            $rawBody = $response->body();
            $data = $response->json();

            Log::info('FeexPay payout raw response', [
                'reference'   => $reference,
                'http_status' => $response->status(),
                'raw_body'    => mb_substr($rawBody, 0, 500),
                'parsed'      => $data,
                'phone'       => $normalized['phone'],
                'network'     => $network,
                'amount'      => $amount,
            ]);

            if ($response->successful() && isset($data['status']) && $data['status'] === 'SUCCESSFUL') {
                Log::info('FeexPay payout success', [
                    'reference'        => $reference,
                    'feexpay_reference' => $data['reference'] ?? null,
                    'phone'            => $normalized['phone'],
                    'amount'           => $amount,
                ]);
                return ['success' => true, 'message' => 'Payout FeexPay effectue vers ' . $normalized['phone'] . '.', 'reference' => $data['reference'] ?? null];
            }

            Log::error('FeexPay payout failed', [
                'reference'   => $reference,
                'phone'       => $normalized['phone'],
                'http_status' => $response->status(),
                'raw_body'    => mb_substr($rawBody, 0, 500),
            ]);

            return ['success' => false, 'message' => 'Payout FeexPay echoue (HTTP ' . $response->status() . '). Transfert manuel requis vers ' . $phone . '.', 'reference' => null];
        } catch (\Throwable $e) {
            Log::error('FeexPay payout exception', ['reference' => $reference, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur technique payout FeexPay: ' . $e->getMessage(), 'reference' => null];
        }
    }

    // ──────────────────────────────────────────────
    //  Remboursement
    // ──────────────────────────────────────────────

    public static function refundOrder(Order $order): array
    {
        if (! $order->payment_gateway_ref) {
            return ['success' => false, 'message' => 'Pas de reference passerelle — remboursement manuel requis pour la commande ' . $order->reference . '.'];
        }

        // FeexPay n'a pas d'API de remboursement native.
        // On utilise le payout (transfert global) vers le numero du client.
        $phone = $order->customer_phone ?? $order->customer_whatsapp ?? '';
        if (! $phone) {
            return ['success' => false, 'message' => 'ATTENTION: Remboursement FeexPay impossible (pas de numero client). Remboursez manuellement ' . number_format((float) $order->amount_paid, 0, ',', ' ') . ' FCFA au client. Ref: ' . $order->payment_gateway_ref];
        }

        $amount = (int) round((float) $order->amount_paid);
        $motif  = 'Remboursement commande ' . ($order->reference ?? $order->id);

        $result = self::payout($phone, $amount, $order->reference ?? '', $motif);

        if ($result['success']) {
            Log::info('FeexPay refund success', [
                'order_id'         => $order->id,
                'reference'        => $order->reference,
                'feexpay_reference' => $result['reference'] ?? null,
                'phone'            => $phone,
                'amount'           => $amount,
            ]);
            return ['success' => true, 'message' => 'Remboursement FeexPay effectue via Mobile Money au ' . $phone . '.'];
        }

        Log::error('FeexPay refund failed', [
            'order_id'    => $order->id,
            'gateway_ref' => $order->payment_gateway_ref,
            'phone'       => $phone,
            'reason'      => $result['message'],
        ]);

        return ['success' => false, 'message' => 'ATTENTION: Remboursement FeexPay echoue. Remboursez manuellement ' . number_format((float) $order->amount_paid, 0, ',', ' ') . ' FCFA au ' . $phone . '. Ref: ' . $order->payment_gateway_ref];
    }
}
