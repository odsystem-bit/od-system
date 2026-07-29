<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ClickLog;
use App\Models\Order;
use App\Models\SmartLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TopRankingController extends Controller
{
    /**
     * Top 100 Créateurs de Contenu — classés par CA généré (commissions + ventes).
     */
    public function creators(Request $request): InertiaResponse
    {
        $year = (int) ($request->query('year') ?: now()->year);

        $creators = User::where('role', UserRole::INFLUENCER)
            ->select('users.id', 'users.name', 'users.slug', 'users.profile_photo', 'users.is_ambassador', 'users.ambassador_tier', 'users.country', 'users.created_at',
                'users.tiktok_followers', 'users.instagram_followers', 'users.facebook_followers', 'users.youtube_followers', 'users.snapchat_followers')
            ->selectRaw('(
                SELECT COALESCE(SUM(o.commission_amount), 0)
                FROM orders o
                WHERE o.influencer_id = users.id
                AND o.status = ?
                AND YEAR(o.created_at) = ?
            ) as total_commissions', [OrderStatus::DELIVERED->value, $year])
            ->selectRaw('(
                SELECT COUNT(*)
                FROM orders o2
                WHERE o2.influencer_id = users.id
                AND o2.status = ?
                AND YEAR(o2.created_at) = ?
            ) as total_orders', [OrderStatus::DELIVERED->value, $year])
            ->selectRaw('(
                SELECT COUNT(*)
                FROM smart_links sl
                INNER JOIN click_logs cl ON cl.smart_link_id = sl.id
                WHERE sl.influencer_id = users.id
                AND cl.is_valid = 1
                AND YEAR(cl.created_at) = ?
            ) as total_clicks', [$year])
            ->selectRaw('(
                SELECT COUNT(DISTINCT sl2.campaign_id)
                FROM smart_links sl2
                WHERE sl2.influencer_id = users.id
                AND EXISTS (
                    SELECT 1 FROM click_logs cl2 WHERE cl2.smart_link_id = sl2.id AND YEAR(cl2.created_at) = ?
                )
            ) as campaigns_participated', [$year])
            ->having('total_commissions', '>', 0)
            ->orHaving('total_orders', '>', 0)
            ->orHaving('total_clicks', '>', 0)
            ->orderByDesc('total_commissions')
            ->orderByDesc('total_orders')
            ->orderByDesc('total_clicks')
            ->limit(100)
            ->get()
            ->map(function ($u, $index) {
                $u->rank = $index + 1;
                $u->total_followers = ($u->tiktok_followers ?? 0)
                    + ($u->instagram_followers ?? 0)
                    + ($u->facebook_followers ?? 0)
                    + ($u->youtube_followers ?? 0)
                    + ($u->snapchat_followers ?? 0);
                return $u;
            });

        $availableYears = range(now()->year, max(2024, now()->year - 3), -1);

        return Inertia::render('Rankings/Creators', [
            'creators'       => $creators,
            'year'           => $year,
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Top 100 Vendeurs — classés par CA total (vendor_earnings).
     */
    public function vendors(Request $request): InertiaResponse
    {
        $year = (int) ($request->query('year') ?: now()->year);

        $vendors = User::where('role', UserRole::VENDOR)
            ->select('users.id', 'users.name', 'users.slug', 'users.profile_photo', 'users.shop_name', 'users.business_name', 'users.is_ambassador', 'users.country', 'users.created_at')
            ->selectRaw('(
                SELECT COALESCE(SUM(o.vendor_earnings), 0)
                FROM orders o
                WHERE o.vendor_id = users.id
                AND o.status = ?
                AND YEAR(o.created_at) = ?
            ) as total_revenue', [OrderStatus::DELIVERED->value, $year])
            ->selectRaw('(
                SELECT COUNT(*)
                FROM orders o2
                WHERE o2.vendor_id = users.id
                AND o2.status = ?
                AND YEAR(o2.created_at) = ?
            ) as total_orders', [OrderStatus::DELIVERED->value, $year])
            ->selectRaw('(
                SELECT COALESCE(SUM(o3.amount_paid), 0)
                FROM orders o3
                WHERE o3.vendor_id = users.id
                AND o3.status = ?
                AND YEAR(o3.created_at) = ?
            ) as total_sales_volume', [OrderStatus::DELIVERED->value, $year])
            ->selectRaw('(
                SELECT COUNT(DISTINCT o4.influencer_id)
                FROM orders o4
                WHERE o4.vendor_id = users.id
                AND o4.status = ?
                AND o4.influencer_id IS NOT NULL
                AND YEAR(o4.created_at) = ?
            ) as creators_worked_with', [OrderStatus::DELIVERED->value, $year])
            ->selectRaw('(
                SELECT COUNT(*)
                FROM campaigns c
                WHERE c.vendor_id = users.id
                AND YEAR(c.created_at) = ?
            ) as total_campaigns', [$year])
            ->having('total_revenue', '>', 0)
            ->orHaving('total_orders', '>', 0)
            ->orderByDesc('total_revenue')
            ->orderByDesc('total_orders')
            ->limit(100)
            ->get()
            ->map(function ($u, $index) {
                $u->rank = $index + 1;
                return $u;
            });

        $availableYears = range(now()->year, max(2024, now()->year - 3), -1);

        return Inertia::render('Rankings/Vendors', [
            'vendors'        => $vendors,
            'year'           => $year,
            'availableYears' => $availableYears,
        ]);
    }
}
