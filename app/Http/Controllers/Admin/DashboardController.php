<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\OrderDisputeMessage;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderMessage;
use App\Models\TicketMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * DashboardController — Tableau de bord administrateur MANTOTA.
 *
 * KPI Plateforme + Sparklines + Repartition geographique.
 */
class DashboardController extends Controller
{
    public function index(): InertiaResponse
    {
        $data = Cache::remember('admin.dashboard', 600, function () {
        // ── KPI Utilisateurs ──
        $totalVendors     = User::where('role', UserRole::VENDOR)->count();
        $totalInfluencers = User::where('role', UserRole::INFLUENCER)->count();
        $totalUsers       = $totalVendors + $totalInfluencers;

        // ── KPI Financier ──
        $totalDeposits = (float) Transaction::where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount_total');

        $totalWithdrawals = (float) Transaction::where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount_target');

        // Escrow reel = commandes e-commerce actives + commandes UGC actives
        // (Wallet::sum double-compte les ServiceOrders car vendor + influencer ont chacun le montant)
        $orderEscrow = (float) Order::whereIn('status', [
                \App\Enums\OrderStatus::PENDING,
                \App\Enums\OrderStatus::SHIPPED,
                \App\Enums\OrderStatus::DISPUTED,
            ])
            ->where('payment_status', 'paid')
            ->selectRaw('SUM(COALESCE(vendor_earnings, 0) + COALESCE(commission_amount, 0)) as total')
            ->value('total');

        $serviceOrderEscrow = (float) ServiceOrder::whereIn('status', [
                ServiceOrder::STATUS_PENDING,
                ServiceOrder::STATUS_SHOOTING,
                ServiceOrder::STATUS_DELIVERED,
                ServiceOrder::STATUS_REVISION_REQUESTED,
                ServiceOrder::STATUS_DISPUTED,
            ])
            ->sum('amount');

        $totalEscrow = ($orderEscrow ?? 0) + ($serviceOrderEscrow ?? 0);

        // Profits reels MANTOTA = markups transactions + marge commandes
        $mantotaProfits = (float) Transaction::where('status', 'completed')
            ->sum('mantota_markup');
        $mantotaProfits += (float) Order::where('status', 'completed')
            ->selectRaw("SUM(COALESCE(amount_paid,0) - COALESCE(vendor_earnings,0) - COALESCE(commission_amount,0) - COALESCE(delivery_fee_paid,0)) as total")
            ->value('total');

        // ── Revenus mensuels MANTOTA (6 derniers mois) ──
        $monthlyRevenue = Transaction::where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("SUM(CASE WHEN type = 'deposit' THEN mantota_markup ELSE 0 END) as markups")
            ->selectRaw("SUM(CASE WHEN type = 'deposit' THEN gateway_fee ELSE 0 END) as deposit_fees")
            ->selectRaw("SUM(CASE WHEN type = 'withdrawal' THEN mantota_markup ELSE 0 END) as withdrawal_commissions")
            ->selectRaw("SUM(CASE WHEN type = 'earning' THEN mantota_markup ELSE 0 END) as ugc_commissions")
            ->selectRaw("SUM(CASE WHEN type = 'fee' THEN mantota_markup ELSE 0 END) as campaign_commissions")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        // Revenue from orders (platform cut = amount_paid - vendor_earnings - commission_amount - delivery_fee_paid)
        $monthlyOrderRevenue = Order::where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("SUM(COALESCE(amount_paid,0) - COALESCE(vendor_earnings,0) - COALESCE(commission_amount,0) - COALESCE(delivery_fee_paid,0)) as platform_fees")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Build 6-month array
        $revenueMonths = collect(range(5, 0, -1))->map(function ($i) use ($monthlyRevenue, $monthlyOrderRevenue) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $label = Carbon::now()->subMonths($i)->translatedFormat('M Y');
            $txRow = $monthlyRevenue->firstWhere('month', $month);
            $orderRow = $monthlyOrderRevenue->get($month);
            return [
                'month'                   => $month,
                'label'                   => $label,
                'markups'                 => (float) ($txRow->markups ?? 0),
                'deposit_fees'            => (float) ($txRow->deposit_fees ?? 0),
                'withdrawal_commissions'  => (float) ($txRow->withdrawal_commissions ?? 0),
                'ugc_commissions'         => (float) ($txRow->ugc_commissions ?? 0),
                'campaign_commissions'    => (float) ($txRow->campaign_commissions ?? 0),
                'order_platform_fees'     => (float) ($orderRow->platform_fees ?? 0),
                'total'                   => (float) ($txRow->markups ?? 0)
                                           + (float) ($txRow->withdrawal_commissions ?? 0)
                                           + (float) ($txRow->ugc_commissions ?? 0)
                                           + (float) ($txRow->campaign_commissions ?? 0)
                                           + (float) ($orderRow->platform_fees ?? 0),
            ];
        })->values();

        // Yearly totals
        $currentYear = Carbon::now()->year;
        $yearlyMarkups = (float) Transaction::where('status', 'completed')
            ->whereYear('created_at', $currentYear)
            ->sum('mantota_markup');
        $yearlyOrderFees = (float) Order::where('status', 'completed')
            ->whereYear('created_at', $currentYear)
            ->selectRaw("SUM(COALESCE(amount_paid,0) - COALESCE(vendor_earnings,0) - COALESCE(commission_amount,0) - COALESCE(delivery_fee_paid,0)) as total")
            ->value('total');
        $yearlyTotal = $yearlyMarkups + (float) $yearlyOrderFees;

        // ── Sparklines : 7 derniers jours ──
        $sparkDays = collect(range(6, 0, -1))->map(fn ($d) => Carbon::today()->subDays($d)->toDateString());

        // Depots par jour
        $depositsPerDay = Transaction::where('type', 'deposit')
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->selectRaw('DATE(created_at) as day, SUM(amount_total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $sparkDeposits = $sparkDays->map(fn ($d) => (float) ($depositsPerDay[$d] ?? 0))->values();

        // Nouveaux vendeurs par jour
        $vendorsPerDay = User::where('role', UserRole::VENDOR)
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $sparkVendors = $sparkDays->map(fn ($d) => (int) ($vendorsPerDay[$d] ?? 0))->values();

        // Nouveaux créateurs de contenu par jour
        $influencersPerDay = User::where('role', UserRole::INFLUENCER)
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $sparkInfluencers = $sparkDays->map(fn ($d) => (int) ($influencersPerDay[$d] ?? 0))->values();

        // Commandes par jour (sparkline)
        $ordersPerDay = Order::where('created_at', '>=', Carbon::today()->subDays(6))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $sparkOrders = $sparkDays->map(fn ($d) => (int) ($ordersPerDay[$d] ?? 0))->values();

        // ── Commandes & Catalogue ──
        $totalOrders   = Order::count();
        $ordersToday   = Order::whereDate('created_at', Carbon::today())->count();
        $ordersWeek    = Order::where('created_at', '>=', Carbon::now()->startOfWeek())->count();
        $totalProducts = Product::count();
        $totalCampaigns = Campaign::count();

        // ── Dernieres commandes ──
        $recentOrders = Order::with('product:id,name')
            ->latest()
            ->take(8)
            ->get()
            ->map(fn ($o) => [
                'id'            => $o->id,
                'product_name'  => $o->product?->name ?? 'Produit supprime',
                'customer_name' => $o->customer_name,
                'customer_email'=> $o->customer_email,
                'status'        => $o->status instanceof \BackedEnum ? $o->status->value : $o->status,
                'amount_paid'   => (float) $o->amount_paid,
                'created_at'    => $o->created_at,
            ]);

        // ── Repartition geographique (top 10 pays) ──
        $countryStats = User::whereIn('role', [UserRole::VENDOR, UserRole::INFLUENCER])
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->selectRaw('country, COUNT(*) as total, 
                SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as vendors,
                SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as influencers', 
                [UserRole::VENDOR->value, UserRole::INFLUENCER->value])
            ->groupBy('country')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── Alertes ──
        $pendingKycCount        = User::where('kyc_status', 'pending')->count();
        $pendingVipCount        = $this->countPendingVipRequests();
        $pendingWithdrawalCount = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->count();

        // Messages signales (conversations douteuses)
        $flaggedMessagesCount = ServiceOrderMessage::where('is_flagged', true)->count()
            + OrderDisputeMessage::where('is_flagged', true)->count()
            + TicketMessage::where('is_flagged', true)->count();

        // ── Tableaux (5 max) ──
        $latestPendingKyc = User::where('kyc_status', 'pending')
            ->select('id', 'name', 'email', 'role', 'country', 'created_at')
            ->latest()
            ->take(5)
            ->get();

        $latestPendingWithdrawals = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->with('user:id,name,email')
            ->latest()
            ->take(5)
            ->get();

        $latestPendingVip = User::where('role', UserRole::INFLUENCER)
            ->where('is_vip', false)
            ->whereNotNull('vip_requested_at')
            ->select(
                'id', 'name', 'email',
                'tiktok_url', 'tiktok_followers',
                'instagram_url', 'instagram_followers',
                'facebook_url', 'facebook_followers',
                'youtube_url', 'youtube_followers',
                'snapchat_url', 'snapchat_followers',
                'created_at'
            )
            ->latest()
            ->take(5)
            ->get();

        // ── Rapport Livraison : volume de commandes par service de livraison ──
        $deliveryStats = Order::whereNotNull('delivery_company')
            ->where('delivery_company', '!=', '')
            ->selectRaw('delivery_company, COUNT(*) as total')
            ->groupBy('delivery_company')
            ->orderByDesc('total')
            ->get();

        $totalShippedOrders = (int) Order::whereNotNull('delivery_company')
            ->where('delivery_company', '!=', '')
            ->count();

        return [
            // KPI
            'totalUsers'        => $totalUsers,
            'totalVendors'      => $totalVendors,
            'totalInfluencers'  => $totalInfluencers,
            'totalDeposits'     => $totalDeposits,
            'totalWithdrawals'  => $totalWithdrawals,
            'totalEscrow'       => $totalEscrow,
            'mantotaProfits'    => $mantotaProfits,

            // Revenus mensuels
            'revenueMonths'     => $revenueMonths,
            'yearlyTotal'       => $yearlyTotal,
            'currentYear'       => $currentYear,

            // Commandes & Catalogue
            'totalOrders'       => $totalOrders,
            'ordersToday'       => $ordersToday,
            'ordersWeek'        => $ordersWeek,
            'totalProducts'     => $totalProducts,
            'totalCampaigns'    => $totalCampaigns,
            'recentOrders'      => $recentOrders,

            // Sparklines (7 jours)
            'sparkDeposits'     => $sparkDeposits,
            'sparkVendors'      => $sparkVendors,
            'sparkInfluencers'  => $sparkInfluencers,
            'sparkOrders'       => $sparkOrders,

            // Geographie
            'countryStats'      => $countryStats,

            // Alertes
            'pendingKycCount'        => $pendingKycCount,
            'pendingVipCount'        => $pendingVipCount,
            'pendingWithdrawalCount' => $pendingWithdrawalCount,
            'flaggedMessagesCount'   => $flaggedMessagesCount,

            // Tableaux
            'latestPendingKyc'         => $latestPendingKyc,
            'latestPendingWithdrawals' => $latestPendingWithdrawals,
            'latestPendingVip'         => $latestPendingVip,

            // Rapport Livraison
            'deliveryStats'       => $deliveryStats,
            'totalShippedOrders'  => $totalShippedOrders,
        ];
        }); // end Cache::remember

        $data['ambassadors'] = User::where('is_ambassador', true)->whereNotNull('name')->inRandomOrder()->limit(12)->get(['id', 'name', 'profile_photo', 'role', 'shop_name', 'business_name', 'shop_logo_path']);

        return Inertia::render('Dashboard', $data);
    }

    private function countPendingVipRequests(): int
    {
        return User::where('role', UserRole::INFLUENCER)
            ->where('is_vip', false)
            ->whereNotNull('vip_requested_at')
            ->count();
    }
}
