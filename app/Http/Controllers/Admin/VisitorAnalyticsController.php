<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VisitorAnalyticsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $range = $request->input('range', '7d');
        $since = match ($range) {
            'today' => Carbon::today(),
            '7d'    => Carbon::now()->subDays(7),
            '30d'   => Carbon::now()->subDays(30),
            '90d'   => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(7),
        };

        $cacheKey = "admin.visitors.{$range}";
        $cacheTtl = $range === 'today' ? 120 : 300;

        $data = Cache::remember($cacheKey, $cacheTtl, function () use ($since, $range) {

            // ── KPI Cards ──
            $totalViews = PageVisit::where('created_at', '>=', $since)->count();
            $uniqueVisitors = PageVisit::where('created_at', '>=', $since)
                ->distinct('ip_address')->count('ip_address');
            $avgTimeSpent = PageVisit::where('created_at', '>=', $since)
                ->whereNotNull('time_spent')
                ->where('time_spent', '>', 0)
                ->avg('time_spent');

            // Bounce rate = sessions with only 1 page view / total sessions
            $sessionCounts = PageVisit::where('created_at', '>=', $since)
                ->select('session_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('session_id')
                ->get();
            $totalSessions = $sessionCounts->count();
            $bounceSessions = $sessionCounts->where('cnt', 1)->count();
            $bounceRate = $totalSessions > 0 ? round($bounceSessions / $totalSessions * 100, 1) : 0;

            // ── Daily Traffic Chart (last 30 days max) ──
            $dailyQuery = PageVisit::where('created_at', '>=', $since)
                ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT ip_address) as visitors'))
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $dailyTraffic = $dailyQuery->map(fn($row) => [
                'day'      => Carbon::parse($row->day)->format('d/m'),
                'views'    => $row->views,
                'visitors' => $row->visitors,
            ])->values();

            // ── Hourly Traffic (today) ──
            $hourlyTraffic = PageVisit::where('created_at', '>=', Carbon::today())
                ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as views'))
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->map(fn($r) => ['hour' => str_pad($r->hour, 2, '0', STR_PAD_LEFT) . 'h', 'views' => $r->views])
                ->values();

            // ── Top Countries ──
            $topCountries = PageVisit::where('created_at', '>=', $since)
                ->whereNotNull('country')
                ->select('country', 'country_code', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT ip_address) as visitors'))
                ->groupBy('country', 'country_code')
                ->orderByDesc('visitors')
                ->limit(15)
                ->get();

            // ── Top Pages ──
            $topPages = PageVisit::where('created_at', '>=', $since)
                ->select('page_url', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT ip_address) as visitors'), DB::raw('AVG(CASE WHEN time_spent > 0 THEN time_spent END) as avg_time'))
                ->groupBy('page_url')
                ->orderByDesc('views')
                ->limit(20)
                ->get()
                ->map(fn($r) => [
                    'page_url' => $r->page_url,
                    'views'    => $r->views,
                    'visitors' => $r->visitors,
                    'avg_time' => $r->avg_time ? round($r->avg_time) : null,
                ]);

            // ── Device Breakdown ──
            $devices = PageVisit::where('created_at', '>=', $since)
                ->select('device_type', DB::raw('COUNT(*) as total'))
                ->groupBy('device_type')
                ->orderByDesc('total')
                ->get();

            // ── Browser Breakdown ──
            $browsers = PageVisit::where('created_at', '>=', $since)
                ->select('browser', DB::raw('COUNT(*) as total'))
                ->groupBy('browser')
                ->orderByDesc('total')
                ->get();

            // ── Top Referrers ──
            $topReferrers = PageVisit::where('created_at', '>=', $since)
                ->whereNotNull('referrer')
                ->where('referrer', 'NOT LIKE', '%' . config('app.url') . '%')
                ->select('referrer', DB::raw('COUNT(*) as visits'))
                ->groupBy('referrer')
                ->orderByDesc('visits')
                ->limit(10)
                ->get()
                ->map(fn($r) => [
                    'referrer' => strlen($r->referrer) > 80 ? substr($r->referrer, 0, 80) . '...' : $r->referrer,
                    'visits'   => $r->visits,
                ]);

            // ── Recent Visitors (last 30) ──
            $recentVisitors = PageVisit::with('user:id,name,role')
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn($v) => [
                    'id'          => $v->id,
                    'page_url'    => $v->page_url,
                    'country'     => $v->country,
                    'country_code' => $v->country_code,
                    'city'        => $v->city,
                    'device_type' => $v->device_type,
                    'browser'     => $v->browser,
                    'time_spent'  => $v->time_spent,
                    'user_name'   => $v->user?->name,
                    'user_role'   => $v->user?->role,
                    'ip_address'  => $v->ip_address,
                    'created_at'  => $v->created_at->diffForHumans(),
                ]);

            // ── Country-based recommendations ──
            $recommendations = [];
            if ($topCountries->isNotEmpty()) {
                $topCountry = $topCountries->first();
                $recommendations[] = [
                    'type'  => 'country',
                    'title' => "Marche principal : {$topCountry->country}",
                    'desc'  => "{$topCountry->visitors} visiteurs uniques. Concentrez vos campagnes marketing sur ce pays.",
                    'icon'  => 'globe',
                ];
            }
            if ($devices->isNotEmpty()) {
                $topDevice = $devices->first();
                $pct = $totalViews > 0 ? round($topDevice->total / $totalViews * 100) : 0;
                $recommendations[] = [
                    'type'  => 'device',
                    'title' => "Audience {$topDevice->device_type} dominante ({$pct}%)",
                    'desc'  => "Optimisez l'experience {$topDevice->device_type} en priorite pour maximiser les conversions.",
                    'icon'  => 'device',
                ];
            }
            if ($topPages->isNotEmpty()) {
                $bestPage = $topPages->first();
                $recommendations[] = [
                    'type'  => 'page',
                    'title' => "Page star : {$bestPage['page_url']}",
                    'desc'  => "{$bestPage['views']} vues. Utilisez cette page comme point d'entree pour vos campagnes.",
                    'icon'  => 'star',
                ];
            }
            if ($bounceRate > 60) {
                $recommendations[] = [
                    'type'  => 'bounce',
                    'title' => "Taux de rebond eleve ({$bounceRate}%)",
                    'desc'  => "Ameliorez le contenu de la landing page et ajoutez des call-to-action clairs.",
                    'icon'  => 'warning',
                ];
            }

            return [
                'totalViews'      => $totalViews,
                'uniqueVisitors'  => $uniqueVisitors,
                'avgTimeSpent'    => $avgTimeSpent ? round($avgTimeSpent) : 0,
                'bounceRate'      => $bounceRate,
                'totalSessions'   => $totalSessions,
                'dailyTraffic'    => $dailyTraffic,
                'hourlyTraffic'   => $hourlyTraffic,
                'topCountries'    => $topCountries,
                'topPages'        => $topPages,
                'devices'         => $devices,
                'browsers'        => $browsers,
                'topReferrers'    => $topReferrers,
                'recentVisitors'  => $recentVisitors,
                'recommendations' => $recommendations,
            ];
        });

        $data['range'] = $range;

        return Inertia::render('VisitorAnalytics', $data);
    }

    /**
     * Receive time-spent beacon from the frontend.
     */
    public function trackTime(Request $request)
    {
        $request->validate([
            'page_url'   => 'required|string|max:500',
            'time_spent' => 'required|integer|min:1|max:7200',
        ]);

        // Update the latest visit for this session + page
        PageVisit::where('session_id', $request->session()->getId())
            ->where('page_url', $request->input('page_url'))
            ->orderByDesc('id')
            ->limit(1)
            ->update(['time_spent' => $request->input('time_spent')]);

        return response()->noContent();
    }
}
