<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\SmartLink;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AnalyticsController extends Controller
{
    public function index(): InertiaResponse
    {
        $user        = auth()->user();
        $campaignIds = Campaign::where('vendor_id', $user->id)->pluck('id');
        $smartLinkIds = SmartLink::whereIn('campaign_id', $campaignIds)->pluck('id');

        // Daily clicks for last 30 days
        $dailyClicks = DB::table('click_logs')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid, SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END) as invalid')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        // Per-campaign performance (single efficient query)
        $campaignStats = Campaign::where('vendor_id', $user->id)
            ->whereIn('status', ['active', 'paused', 'expired'])
            ->select('id', 'title', 'total_budget', 'remaining_budget', 'click_price', 'status')
            ->addSelect([
                'total_clicks' => DB::table('click_logs')
                    ->join('smart_links', 'smart_links.id', '=', 'click_logs.smart_link_id')
                    ->whereColumn('smart_links.campaign_id', 'campaigns.id')
                    ->selectRaw('COUNT(*)'),
                'paid_clicks' => DB::table('click_logs')
                    ->join('smart_links', 'smart_links.id', '=', 'click_logs.smart_link_id')
                    ->whereColumn('smart_links.campaign_id', 'campaigns.id')
                    ->where('click_logs.is_paid', true)
                    ->selectRaw('COUNT(*)'),
            ])
            ->get()
            ->each(fn ($c) => $c->spent = (float) $c->total_budget - (float) $c->remaining_budget);

        // Click reasons breakdown
        $invalidReasons = DB::table('click_logs')
            ->selectRaw('invalid_reason, COUNT(*) as total')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->where('is_valid', false)
            ->whereNotNull('invalid_reason')
            ->groupBy('invalid_reason')
            ->get();

        // Top countries
        $topCountries = DB::table('click_logs')
            ->selectRaw('clicker_country, COUNT(*) as total')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->whereNotNull('clicker_country')
            ->groupBy('clicker_country')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return Inertia::render('Analytics', [
            'dailyClicks'    => $dailyClicks,
            'campaignStats'  => $campaignStats,
            'invalidReasons' => $invalidReasons,
            'topCountries'   => $topCountries,
        ]);
    }
}
