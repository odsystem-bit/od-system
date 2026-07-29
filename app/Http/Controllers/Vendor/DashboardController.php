<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Gateway;
use App\Models\KycLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\SmartLink;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * DashboardController — Tableau de bord du vendeur MANTOTA.
 *
 * Responsabilités :
 *  • Afficher les KPI complets : total depense, clics obtenu, ventes partenaires.
 *  • Transmettre le kyc_status pour le gate front-end.
 *  • Afficher le solde principal + escrow du wallet.
 *
 * La création de campagne est déléguée à Vendor\CampaignController.
 * Les dépôts sont gérés par Payment\FedaPayController.
 */
class DashboardController extends Controller
{
    /**
     * Affiche le dashboard du vendeur avec wallet, stats, campagnes et kyc_status.
     */
    public function index(): InertiaResponse
    {
        $user   = auth()->user();
        $wallet = $user->wallet;

        // ── IDs des campagnes du vendeur (pour les sous-requêtes) ──
        $campaignIds = Campaign::where('vendor_id', $user->id)->pluck('id');

        // ── Statistiques agrégées — spec dashboard ──
        $activeCampaignsCount = Campaign::query()
            ->where('vendor_id', $user->id)
            ->where('status', CampaignStatus::ACTIVE)
            ->count();

        $totalSpent = Campaign::query()
            ->where('vendor_id', $user->id)
            ->whereIn('status', [
                CampaignStatus::ACTIVE,
                CampaignStatus::PAUSED,
                CampaignStatus::EXPIRED,
            ])
            ->selectRaw('COALESCE(SUM(total_budget - remaining_budget), 0) as spent')
            ->value('spent');

        // Total clics obtenus : somme des clics enregistres pour les SmartLinks du vendeur
        $smartLinkIds = SmartLink::whereIn('campaign_id', $campaignIds)->pluck('id');

        $totalClicks = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->count();

        // Clics payes (valides)
        $paidClicks = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->where('is_paid', true)
            ->count();

        // Taux de clic (clics payes / clics totaux)
        $clickRate = $totalClicks > 0
            ? round(($paidClicks / $totalClicks) * 100, 1)
            : 0;

        // Total ventes partenaires : commandes reelles (hors annulees)
        $totalPartnerSales = Order::where('vendor_id', $user->id)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->count();

        $stats = [
            'active_campaigns'      => $activeCampaignsCount,
            'total_spent'           => (float) $totalSpent,
            'total_clicks'          => $totalClicks,
            'paid_clicks'           => $paidClicks,
            'click_rate'            => $clickRate,
            'total_partner_sales'   => $totalPartnerSales,
            'available_balance'     => $wallet ? (float) $wallet->balance : 0.00,
            'escrow_balance'        => $wallet ? (float) $wallet->escrow_balance : 0.00,
        ];

        // ── Campagnes du vendor avec compteur de SmartLinks ──
        $campaigns = Campaign::query()
            ->where('vendor_id', $user->id)
            ->withCount('smartLinks')
            ->latest()
            ->paginate(15);

        // ── Dernières transactions (10) ──
        $transactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        // Utilise les frais reels du gateway actif (payin_fee stocke dans la table gateways)
        $activeGateway     = Gateway::where('is_active', true)->orderBy('priority')->first();
        $gatewayFeePercent = $activeGateway ? (float) $activeGateway->payin_fee : (float) mantota_setting('fedapay_fee_percent', 1.5);

        // Derniere raison de rejet KYC (pour popup front-end)
        $kycRejectionReason = null;
        if (($user->kyc_status ?? '') === 'rejected') {
            $lastReject = KycLog::where('user_id', $user->id)
                ->where('action', 'rejected')
                ->latest()
                ->first();
            $kycRejectionReason = $lastReject?->reason;
        }

        // ── Onboarding checklist ──
        $onboarding = [
            'email_verified'  => $user->hasVerifiedEmail(),
            'profile_done'    => ! empty($user->shop_name) || ! empty($user->business_name),
            'kyc_submitted'   => ! in_array($user->kyc_status ?? 'not_submitted', ['not_submitted', 'rejected']),
            'product_added'   => Product::where('vendor_id', $user->id)->exists(),
            'campaign_created' => $campaignIds->isNotEmpty(),
        ];

        return Inertia::render('Dashboard', [
            'wallet'               => $wallet,
            'stats'                => $stats,
            'campaigns'            => $campaigns,
            'transactions'         => $transactions,
            'kyc_status'           => $user->kyc_status ?? 'not_submitted',
            'kyc_rejection_reason' => $kycRejectionReason,
            'deposit_markup_percent' => (float) mantota_setting('deposit_markup_percent', 1.5),
            'gateway_fee_percent'  => $gatewayFeePercent,
            'onboarding'           => $onboarding,
            'ambassadors'          => User::where('is_ambassador', true)->whereNotNull('name')->inRandomOrder()->limit(12)->get(['id', 'name', 'profile_photo', 'role', 'shop_name', 'business_name', 'shop_logo_path']),
            'referral'             => [
                'code'     => $user->referral_code,
                'count'    => (int) $user->referral_count,
                'earnings' => (float) $user->referral_earnings,
            ],
        ]);
    }
}
