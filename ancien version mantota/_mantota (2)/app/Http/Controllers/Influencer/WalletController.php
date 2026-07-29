<?php

declare(strict_types=1);

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Payment\FeexPayService;
use App\Services\Payment\GatewayResolver;
use App\Services\Payment\PayDunyaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * WalletController — Gestion du portefeuille du créateur de contenu MANTOTA.
 *
 * Responsabilités :
 *  • Afficher le solde, le pending_balance et l'historique des transactions.
 *  • Traiter les demandes de retrait avec triple vérification de sécurité
 *    (KYC approuvé, montant minimum, solde suffisant).
 *  • Déplacer atomiquement les fonds de balance → pending_balance via
 *    DB::transaction() + lockForUpdate().
 *
 * Les calculs financiers (commission 20 %, frais gateway) sont délégués
 * à FedaPayService pour respecter le principe de responsabilité unique.
 */
class WalletController extends Controller
{
    // ──────────────────────────────────────────────
    //  1. Affichage du portefeuille
    // ──────────────────────────────────────────────

    /**
     * Charge le wallet, l'historique paginé et le statut KYC du créateur de contenu.
     */
    public function index(): InertiaResponse
    {
        $user   = auth()->user();
        $wallet = $user->wallet;

        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $activeGateway = GatewayResolver::resolve($user->country ?? null);
        $gatewayFeePercent = $activeGateway
            ? (float) $activeGateway->payin_fee
            : (float) mantota_setting('paydunya_fee_percent', 2.0);
        $payoutFeePercent = $activeGateway
            ? (float) $activeGateway->payout_fee
            : 1.0;

        return Inertia::render('Wallet/Index', [
            'wallet'              => $wallet,
            'transactions'        => $transactions,
            'kyc_status'          => $user->kyc_status ?? 'pending',
            'min_withdrawal'      => (int) mantota_setting('min_withdrawal_amount', 1000),
            'min_deposit'         => (int) mantota_setting('min_deposit_amount', 1000),
            'withdrawal_fee_percent' => (float) mantota_setting('withdrawal_fee_percent', 20),
            'platform_commission_rate' => (float) mantota_setting('platform_commission_rate', 20),
            'deposit_markup_percent' => (float) mantota_setting('deposit_markup_percent', 1.5),
            'gateway_fee_percent' => $gatewayFeePercent,
            'payout_fee_percent'  => $payoutFeePercent,
            'momo_number'         => $user->phone ?? $user->whatsapp_number ?? '',
            'referral_transfer_threshold' => (int) mantota_setting('referral_transfer_threshold', 10000),
            'referral_code'       => $user->referral_code,
            'referral_count'      => $user->referral_count ?? 0,
            'referral_earnings'   => (float) ($user->referral_earnings ?? 0),
            'is_ambassador'           => (bool) $user->is_ambassador,
            'ambassador_badge_price'  => (int) mantota_setting('ambassador_badge_price', 5000),
            'ambassador_sale_enabled' => (bool) mantota_setting('ambassador_sale_enabled', false),
            'ambassador_expires_at'   => $user->ambassador_expires_at?->toDateString(),
            'ambassador_days_left'    => $user->ambassador_expires_at ? (int) max(0, now()->diffInDays($user->ambassador_expires_at, false)) : null,
            'ambassador_subscription_duration' => (int) mantota_setting('ambassador_subscription_duration', 30),
        ]);
    }

    // ──────────────────────────────────────────────
    //  2. Demande de retrait
    // ──────────────────────────────────────────────

    /**
     * Traite une demande de retrait créateur de contenu avec triple sécurité.
     *
     * Sécurité 1 : KYC approuvé (verrou principal).
     * Sécurité 2 : Montant minimum configurable (défaut: 1 000 FCFA).
     * Sécurité 3 : Solde suffisant dans le wallet.
     *
     * Si toutes les conditions sont remplies :
     *  - Déplace le montant demandé de balance → pending_balance (lockForUpdate).
     *  - Crée une Transaction type 'withdrawal' avec la décomposition complète
     *    (commission MANTOTA 20 %, frais gateway, net payout).
     */
    public function requestWithdrawal(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // ── Portefeuille verrouille ──
        $wallet = Wallet::where('user_id', $user->id)->first();
        if ($wallet && $wallet->isLocked()) {
            return back()->withErrors(['wallet' => 'Votre portefeuille est verrouille. Contactez le support.']);
        }

        // Resolution dynamique du service de paiement
        $activeGateway = GatewayResolver::resolve($user->country ?? null);
        $service = match ($activeGateway?->slug) {
            'feexpay'  => app(FeexPayService::class),
            default    => app(PayDunyaService::class),
        };

        // ── Sécurité 1 : Vérification KYC ──
        if ($user->kyc_status !== 'approved') {
            return back()->withErrors([
                'kyc' => 'Votre identite doit etre verifiee avant de pouvoir effectuer un retrait.',
            ]);
        }

        // ── Sécurité 2 : Validation du montant ──
        $minWithdrawal = (int) mantota_setting('min_withdrawal_amount', 1000);
        $validated = $request->validate([
            'amount'      => ['required', 'numeric', 'min:' . $minWithdrawal],
            'momo_number' => ['required', 'string', 'regex:/^[0-9+\- ]{8,20}$/'],
        ]);

        $requestedAmount = (float) $validated['amount'];

        // ── Sécurité 3 : Solde suffisant ──
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet || (float) $wallet->balance < $requestedAmount) {
            return back()->withErrors([
                'amount' => 'Solde insuffisant pour effectuer ce retrait.',
            ]);
        }

        // ── Calcul de la décomposition financière ──
        $breakdown = $service->calculateWithdrawal($requestedAmount);

        $momoNumber = preg_replace('/[^0-9+]/', '', $validated['momo_number']);

        // ── Transaction atomique : balance → pending_balance + création Transaction ──
        DB::transaction(function () use ($user, $wallet, $requestedAmount, $breakdown, $momoNumber): void {
            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Double vérification post-lock (protection concurrence)
            if ((float) $lockedWallet->balance < $requestedAmount) {
                throw new \RuntimeException('Solde insuffisant apres verrouillage.');
            }

            // Déplacement balance → pending_balance
            $lockedWallet->balance         = (float) $lockedWallet->balance - $requestedAmount;
            $lockedWallet->pending_balance = (float) $lockedWallet->pending_balance + $requestedAmount;
            $lockedWallet->save();

            // Création de la transaction avec décomposition complète
            Transaction::create([
                'user_id'         => $user->id,
                'type'            => 'withdrawal',
                'amount_target'   => $breakdown['requested_amount'],
                'gateway_fee'     => $breakdown['gateway_fee'],
                'mantota_markup'  => $breakdown['mantota_commission'],
                'amount_total'    => $breakdown['net_payout'],
                'status'          => 'pending',
                'reference'       => 'WDR-' . uniqid('', true),
                'description'     => 'Retrait Mobile Money',
                'payment_gateway' => $activeGateway?->slug ?? 'paydunya',
                'momo_number'     => $momoNumber,
            ]);
        });

        return redirect()
            ->route('influencer.wallet.index')
            ->with('success', 'Demande de retrait enregistree. Votre solde sera mis a jour apres validation.');
    }

    // ──────────────────────────────────────────────
    //  3. Transfert solde parrainage → solde principal
    // ──────────────────────────────────────────────

    /**
     * Transfère le solde parrainage vers le solde principal.
     *
     * Le transfert n'est autorisé que si le referral_balance atteint
     * le seuil configurable (défaut 10 000 FCFA).
     */
    public function transferReferralBalance(): RedirectResponse
    {
        $user   = auth()->user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if ($wallet && $wallet->isLocked()) {
            return back()->withErrors(['wallet' => 'Votre portefeuille est verrouille. Contactez le support.']);
        }

        if (! $wallet || (float) $wallet->referral_balance <= 0) {
            return back()->withErrors(['referral' => 'Aucun solde parrainage a transferer.']);
        }

        $threshold = (int) mantota_setting('referral_transfer_threshold', 10000);

        if ((float) $wallet->referral_balance < $threshold) {
            return back()->withErrors([
                'referral' => "Le seuil minimum de transfert est de {$threshold} FCFA. Solde actuel : " . number_format((float) $wallet->referral_balance, 0, ',', ' ') . ' FCFA.',
            ]);
        }

        $amount = (float) $wallet->referral_balance;

        DB::transaction(function () use ($user, $wallet, $amount): void {
            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->referral_balance < $amount) {
                throw new \RuntimeException('Solde parrainage insuffisant apres verrouillage.');
            }

            $locked->referral_balance = 0;
            $locked->balance          = (float) $locked->balance + $amount;
            $locked->save();

            Transaction::create([
                'user_id'        => $user->id,
                'type'           => 'referral_bonus',
                'amount_target'  => $amount,
                'amount_total'   => $amount,
                'gateway_fee'    => 0,
                'mantota_markup' => 0,
                'status'         => 'completed',
                'reference'      => 'RTR-' . uniqid('', true),
                'description'    => 'Transfert solde parrainage vers portefeuille principal',
            ]);
        });

        return redirect()
            ->route('influencer.wallet.index')
            ->with('success', number_format($amount, 0, ',', ' ') . ' FCFA transferes vers votre portefeuille principal.');
    }

    // ──────────────────────────────────────────────
    //  4. Achat du badge ambassadeur
    // ──────────────────────────────────────────────

    /**
     * Permet au créateur de contenu de s'abonner ou renouveler
     * le badge Ambassadeur (abonnement mensuel).
     */
    public function purchaseBadge(): RedirectResponse
    {
        $user = auth()->user();

        // Vérifier portefeuille verrouille
        $walletCheck = Wallet::where('user_id', $user->id)->first();
        if ($walletCheck && $walletCheck->isLocked()) {
            return back()->withErrors(['wallet' => 'Votre portefeuille est verrouille. Contactez le support.']);
        }

        // Vérifier que l'abonnement est activé
        if (! (bool) mantota_setting('ambassador_sale_enabled', false)) {
            return back()->withErrors(['badge' => "L'abonnement ambassadeur n'est pas disponible pour le moment."]);
        }

        $price    = (int) mantota_setting('ambassador_badge_price', 5000);
        $duration = (int) mantota_setting('ambassador_subscription_duration', 30);
        $wallet   = Wallet::where('user_id', $user->id)->first();

        if (! $wallet || (float) $wallet->balance < $price) {
            return back()->withErrors(['badge' => 'Solde insuffisant. Vous avez besoin de ' . number_format($price, 0, ',', ' ') . ' FCFA.']);
        }

        DB::transaction(function () use ($user, $wallet, $price, $duration): void {
            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->balance < $price) {
                throw new \RuntimeException('Solde insuffisant apres verrouillage.');
            }

            $locked->balance = (float) $locked->balance - $price;
            $locked->save();

            // Si déjà ambassadeur avec expiry future, prolonger depuis l'expiry actuelle
            $startFrom = ($user->is_ambassador && $user->ambassador_expires_at && $user->ambassador_expires_at->isFuture())
                ? $user->ambassador_expires_at
                : now();

            Transaction::create([
                'user_id'        => $user->id,
                'type'           => 'fee',
                'amount_target'  => $price,
                'amount_total'   => $price,
                'gateway_fee'    => 0,
                'mantota_markup' => 0,
                'status'         => 'completed',
                'reference'      => 'BADGE-' . uniqid('', true),
                'description'    => 'Abonnement Ambassadeur MANTOTA (' . $duration . ' jours)',
            ]);

            $user->is_ambassador          = true;
            $user->ambassador_tier        = $user->ambassador_tier ?: 'bronze';
            $user->ambassador_source      = 'purchased';
            $user->ambassador_subscribed_at = $user->ambassador_subscribed_at ?: now();
            $user->ambassador_expires_at  = $startFrom->copy()->addDays($duration);
            $user->save();
        });

        return redirect()
            ->route('influencer.wallet.index')
            ->with('success', 'Felicitations ! Votre abonnement Ambassadeur est actif pour ' . $duration . ' jours.');
    }
}
