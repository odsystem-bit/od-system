<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

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
 * WalletController — Portefeuille du vendeur MANTOTA.
 *
 * Responsabilites :
 *  - Afficher le solde principal et le solde escrow.
 *  - Afficher l'historique complet des transactions (pagine).
 *  - Permettre le depot via FedaPay.
 *  - Permettre le retrait (Mobile Money) avec validation admin.
 */
class WalletController extends Controller
{
    /**
     * Affiche la page portefeuille avec les soldes et l'historique des transactions.
     */
    public function index(): InertiaResponse
    {
        $user   = auth()->user();
        $wallet = $user->wallet;

        $transactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

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
            'deposit_markup_percent' => (float) mantota_setting('deposit_markup_percent', 1.5),
            'platform_commission_rate' => (float) mantota_setting('platform_commission_rate', 20),
            'gateway_fee_percent' => $gatewayFeePercent,
            'payout_fee_percent'  => $payoutFeePercent,
            'momo_number'         => $user->phone ?? $user->whatsapp_number ?? '',
            'is_ambassador'           => (bool) $user->is_ambassador,
            'ambassador_badge_price'  => (int) mantota_setting('ambassador_badge_price', 5000),
            'ambassador_sale_enabled' => (bool) mantota_setting('ambassador_sale_enabled', false),
            'ambassador_expires_at'   => $user->ambassador_expires_at?->toDateString(),
            'ambassador_days_left'    => $user->ambassador_expires_at ? (int) max(0, now()->diffInDays($user->ambassador_expires_at, false)) : null,
            'ambassador_subscription_duration' => (int) mantota_setting('ambassador_subscription_duration', 30),
        ]);
    }

    /**
     * Demande de retrait du vendeur (Mobile Money).
     *
     * Securite :
     *  1. KYC approuve obligatoire.
     *  2. Montant minimum configurable (defaut : 1 000 FCFA).
     *  3. Solde suffisant (verifie avec lockForUpdate pour eviter les races).
     *
     * Le montant est deplace de balance → pending_balance et une Transaction
     * de type 'withdrawal' est creee en statut 'pending' pour validation admin.
     */
    public function withdraw(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // ── Portefeuille verrouille ──
        $walletCheck = Wallet::where('user_id', $user->id)->first();
        if ($walletCheck && $walletCheck->isLocked()) {
            return back()->withErrors(['wallet' => 'Votre portefeuille est verrouille. Contactez le support.']);
        }

        // Resolution dynamique du service de paiement
        $activeGateway = GatewayResolver::resolve($user->country ?? null);
        $service = match ($activeGateway?->slug) {
            'feexpay'  => app(FeexPayService::class),
            default    => app(PayDunyaService::class),
        };

        // Securite 1 : KYC approuve
        if (($user->kyc_status ?? 'pending') !== 'approved') {
            return back()->withErrors(['kyc' => 'Votre KYC doit etre approuve avant de pouvoir retirer.']);
        }

        // Securite 2 : Montant minimum (configurable)
        $minWithdrawal = (int) mantota_setting('min_withdrawal_amount', 1000);
        $validated = $request->validate([
            'amount'      => ['required', 'numeric', 'min:' . $minWithdrawal],
            'momo_number' => ['required', 'string', 'regex:/^[0-9+\- ]{8,20}$/'],
        ]);

        $requestedAmount = (float) $validated['amount'];

        // Securite 3 : Solde suffisant (verification hors tx)
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet || (float) $wallet->balance < $requestedAmount) {
            return back()->withErrors(['amount' => 'Solde insuffisant.']);
        }

        // Calcul des frais (meme grille que les créateurs de contenu)
        $breakdown = $service->calculateWithdrawal($requestedAmount);

        $momoNumber = preg_replace('/[^0-9+]/', '', $validated['momo_number']);

        // Transaction atomique : balance → pending_balance + Transaction
        DB::transaction(function () use ($user, $wallet, $requestedAmount, $breakdown, $momoNumber): void {
            $lockedWallet = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Double-check apres verrouillage
            if ((float) $lockedWallet->balance < $requestedAmount) {
                throw new \RuntimeException('Solde insuffisant apres verrouillage.');
            }

            $lockedWallet->balance         = (float) $lockedWallet->balance - $requestedAmount;
            $lockedWallet->pending_balance = (float) $lockedWallet->pending_balance + $requestedAmount;
            $lockedWallet->save();

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
            ->route('vendor.wallet.index')
            ->with('success', 'Demande de retrait soumise. Elle sera traitee apres validation par l\'administration.');
    }

    // ──────────────────────────────────────────────
    //  Achat du badge Meilleure Boutique MANTOTA
    // ──────────────────────────────────────────────

    /**
     * Permet au vendeur de s'abonner ou renouveler
     * le badge Meilleure Boutique (abonnement mensuel).
     */
    public function purchaseBadge(): RedirectResponse
    {
        $user = auth()->user();

        // Vérifier portefeuille verrouille
        $walletCheck = Wallet::where('user_id', $user->id)->first();
        if ($walletCheck && $walletCheck->isLocked()) {
            return back()->withErrors(['wallet' => 'Votre portefeuille est verrouille. Contactez le support.']);
        }

        if (! (bool) mantota_setting('ambassador_sale_enabled', false)) {
            return back()->withErrors(['badge' => "L'abonnement badge n'est pas disponible pour le moment."]);
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
                'description'    => 'Abonnement Meilleure Boutique MANTOTA (' . $duration . ' jours)',
            ]);

            $user->is_ambassador          = true;
            $user->ambassador_tier        = $user->ambassador_tier ?: 'bronze';
            $user->ambassador_source      = 'purchased';
            $user->ambassador_subscribed_at = $user->ambassador_subscribed_at ?: now();
            $user->ambassador_expires_at  = $startFrom->copy()->addDays($duration);
            $user->save();
        });

        return redirect()
            ->route('vendor.wallet.index')
            ->with('success', 'Felicitations ! Votre abonnement Meilleure Boutique est actif pour ' . $duration . ' jours.');
    }
}
