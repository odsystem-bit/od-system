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
 * FedaPayService — Moteur financier de la plateforme MANTOTA.
 *
 * Gère les calculs de marges, la création de transactions et la sécurité webhook.
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │  DÉPÔT (Vendor recharge son wallet) — Reverse Calculation              │
 * │                                                                        │
 * │  1. mantota_markup  = target × (markup_percent / 100)                  │
 * │  2. subtotal        = target + mantota_markup                          │
 * │  3. amount_total    = subtotal / (1 − fedapay_fee_percent / 100)       │
 * │     → Formule inversée : FedaPay prélève ses frais SUR amount_total,   │
 * │       donc on gonfle le montant pour que le net reçu = subtotal.       │
 * │  4. gateway_fee     = amount_total − subtotal                          │
 * ├──────────────────────────────────────────────────────────────────────────┤
 * │  RETRAIT (Créateur de contenu retire ses gains)                                │
 * │  mantota_commission = 20 % du montant demandé                          │
 * │  gateway_fee        = 1.5 % du montant restant après commission        │
 * │  net_payout         = montant demandé − commission − gateway_fee       │
 * └──────────────────────────────────────────────────────────────────────────┘
 *
 * AUCUN stockage JSON — tout transite par MySQL via le modèle Transaction.
 */
final class FedaPayService
{
    // ──────────────────────────────────────────────
    //  Taux de commission (constantes immuables)
    // ──────────────────────────────────────────────

    /** Frais FedaPay par defaut : 1.5 %. Configurable via Admin > Parametres. */
    private const float DEFAULT_FEE_PERCENT = 1.5;

    /** Marge MANTOTA par defaut sur les depots : 2.0 %. */
    private const float DEFAULT_MARKUP_PERCENT = 2.0;

    private function feePercent(): float
    {
        return (float) mantota_setting('fedapay_fee_percent', self::DEFAULT_FEE_PERCENT);
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
    //  1. Calcul d'un dépôt — Reverse Calculation
    // ──────────────────────────────────────────────

    /**
     * Calcule la décomposition financière d'un dépôt vendor via Reverse Calculation.
     *
     * Le vendor souhaite créditer $targetAmount dans son wallet.
     * On ajoute d'abord la marge MANTOTA (2 %), puis on applique la formule
     * inversée pour absorber les frais FedaPay (1.5 %) sans perdre de marge.
     *
     * Exemple pour 10 000 FCFA :
     *   markup  = 10 000 × 0.02            = 200
     *   subtotal = 10 200
     *   total   = 10 200 / (1 − 0.015)     = 10 355.33
     *   fee     = 10 355.33 − 10 200        = 155.33
     *
     * @param  float $targetAmount Montant souhaité dans le wallet (ex : 10 000 FCFA).
     * @return array{target_amount: float, gateway_fee: float, mantota_markup: float, amount_total: float}
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

    // ──────────────────────────────────────────────
    //  2. Création d'une transaction de dépôt
    // ──────────────────────────────────────────────

    /**
     * Crée une transaction de dépôt en base de données (statut : pending).
     *
     * @param  User  $vendor       Le vendor qui effectue le dépôt.
     * @param  float $targetAmount Montant souhaité dans le wallet.
     * @return Transaction         L'enregistrement MySQL créé.
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
            'description'    => 'Recharge compte via FedaPay',
        ]);
    }

    // ──────────────────────────────────────────────
    //  3. Calcul d'un retrait
    // ──────────────────────────────────────────────

    /**
     * Calcule la décomposition financière d'un retrait créateur de contenu.
     *
     * Séquence :
     *  1. Commission MANTOTA = 20 % du montant demandé.
     *  2. Montant après commission = demandé − commission.
     *  3. Frais FedaPay = 1.5 % du montant après commission.
     *  4. Net payout = montant après commission − frais FedaPay.
     *
     * @param  float $requestedAmount Montant que le créateur de contenu souhaite retirer (ex : 5 000 FCFA).
     * @return array{requested_amount: float, mantota_commission: float, gateway_fee: float, net_payout: float}
     */
    public function calculateWithdrawal(float $requestedAmount): array
    {
        $withdrawalRate     = mantota_setting('withdrawal_fee_percent', 20) / 100;
        $mantotaCommission  = round($requestedAmount * $withdrawalRate, 2);
        $afterCommission    = round($requestedAmount - $mantotaCommission, 2);

        // Utiliser le taux payout du gateway et non le taux payin
        $gateway = Gateway::where('slug', 'fedapay')->first();
        $payoutFeeRate = $gateway ? (float) $gateway->payout_fee / 100 : 0.015;
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
    //  4. Vérification de signature webhook
    // ──────────────────────────────────────────────

    /**
     * Vérifie la signature HMAC-SHA256 d'un webhook FedaPay.
     *
     * Compare le hash calculé localement avec la signature transmise
     * dans l'en-tête HTTP du callback, en temps constant (hash_equals)
     * pour prévenir les attaques par timing.
     *
     * @param  string $payload   Corps brut (raw body) de la requête webhook.
     * @param  string $signature Signature transmise par FedaPay (header X-Signature).
     * @param  string $secret    Clé secrète webhook configurée côté FedaPay.
     * @return bool              true si la signature est valide.
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $computedHash = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computedHash, $signature);
    }

    // ──────────────────────────────────────────────
    //  5. Remboursement automatique via FedaPay
    // ──────────────────────────────────────────────

    /**
     * Rembourse le paiement d'une commande via l'API FedaPay.
     *
     * Appelle POST /v1/refunds pour creer un remboursement lie
     * a la transaction FedaPay d'origine (payment_gateway_ref).
     *
     * @param  Order  $order  La commande a rembourser (doit avoir payment_gateway_ref).
     * @return array{success: bool, message: string}
     */
    public static function refundOrder(Order $order): array
    {
        if (! $order->payment_gateway_ref) {
            return ['success' => false, 'message' => 'Pas de reference passerelle — remboursement manuel requis.'];
        }

        try {
            $gateway = Gateway::where('slug', 'fedapay')->where('is_active', true)->first();
            if (! $gateway) {
                return ['success' => false, 'message' => 'Passerelle FedaPay non trouvee.'];
            }

            $apiBase = $gateway->environment === 'live'
                ? 'https://api.fedapay.com'
                : 'https://sandbox-api.fedapay.com';

            $apiKey = $gateway->secret_key ?? '';
            if (! is_string($apiKey) || $apiKey === '') {
                $apiKey = (string) mantota_setting('fedapay_secret_key', '');
            }
            if (! $apiKey) {
                $apiKey = (string) config('services.fedapay.secret_key', '');
            }

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->post("{$apiBase}/v1/refunds", [
                    'transaction_id' => (int) $order->payment_gateway_ref,
                ]);

            if ($response->successful()) {
                Log::info('FedaPay refund success', [
                    'order_id'    => $order->id,
                    'reference'   => $order->reference,
                    'gateway_ref' => $order->payment_gateway_ref,
                    'response'    => $response->json(),
                ]);
                return ['success' => true, 'message' => 'Remboursement FedaPay effectue.'];
            }

            Log::error('FedaPay refund FAILED', [
                'order_id'    => $order->id,
                'gateway_ref' => $order->payment_gateway_ref,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);

            return ['success' => false, 'message' => 'ATTENTION: Remboursement FedaPay echoue (HTTP ' . $response->status() . '). Allez sur le dashboard FedaPay pour rembourser manuellement la transaction #' . $order->payment_gateway_ref . '.'];
        } catch (\Throwable $e) {
            Log::error('FedaPay refund exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erreur technique remboursement : ' . $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────
    //  Refund dispatcher générique (détecte la passerelle)
    // ──────────────────────────────────────────────

    /**
     * Rembourse une commande via la passerelle qui a encaissé le paiement.
     * Détecte automatiquement la passerelle via order.payment_gateway.
     */
    public static function refundAny(Order $order): array
    {
        $gw = $order->payment_gateway;

        if (! $gw) {
            return ['success' => false, 'message' => 'Aucune passerelle enregistree pour cette commande (' . $order->reference . '). Remboursement manuel requis.'];
        }

        return match ($gw) {
            'fedapay'  => self::refundOrder($order),
            'paydunya' => PayDunyaService::refundOrder($order),
            'feexpay'  => FeexPayService::refundOrder($order),
            default    => ['success' => false, 'message' => 'Passerelle "' . $gw . '" ne supporte pas le remboursement automatique. Remboursement manuel requis pour la commande ' . $order->reference . '.'],
        };
    }
}
