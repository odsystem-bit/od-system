<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET (page views), skip admin/api/assets/tracking
        if (
            !$request->isMethod('get')
            || $request->is('admin/*', 'api/*', 'track/*', 'build/*', 'images/*', 'storage/*')
            || $request->ajax()
            || $request->wantsJson()
            || $request->is('*.js', '*.css', '*.png', '*.jpg', '*.svg', '*.ico', '*.woff2')
        ) {
            return $response;
        }

        try {
            $ua = $request->userAgent() ?? '';

            // Skip bots
            if (preg_match('/bot|crawl|spider|slurp|curl|wget|python|java|go-http/i', $ua)) {
                return $response;
            }

            // Device detection
            $deviceType = 'desktop';
            if (preg_match('/Tablet|iPad/i', $ua)) {
                $deviceType = 'tablet';
            } elseif (preg_match('/Mobile|Android|iPhone|iPod/i', $ua)) {
                $deviceType = 'mobile';
            }

            // Browser detection
            $browser = 'Autre';
            if (str_contains($ua, 'Edg')) $browser = 'Edge';
            elseif (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) $browser = 'Opera';
            elseif (str_contains($ua, 'Chrome')) $browser = 'Chrome';
            elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
            elseif (str_contains($ua, 'Safari')) $browser = 'Safari';

            // Geo-IP (cached 24h per IP)
            $ip = $request->ip();
            $geo = $this->resolveGeo($ip);

            PageVisit::create([
                'session_id'   => $request->session()->getId(),
                'user_id'      => $request->user()?->id,
                'ip_address'   => $ip,
                'country'      => $geo['country'] ?? null,
                'country_code' => $geo['countryCode'] ?? null,
                'city'         => $geo['city'] ?? null,
                'page_url'     => '/' . ltrim($request->path(), '/'),
                'referrer'     => $request->header('referer'),
                'device_type'  => $deviceType,
                'browser'      => $browser,
            ]);
        } catch (\Throwable $e) {
            // Silently fail — never break the user experience
            report($e);
        }

        return $response;
    }

    private function resolveGeo(string $ip): array
    {
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return [];
        }

        return Cache::remember('geo_ip_' . md5($ip), 86400, function () use ($ip) {
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 2]]);
                $response = @file_get_contents(
                    "http://ip-api.com/json/{$ip}?fields=country,city,countryCode",
                    false,
                    $ctx
                );
                if ($response) {
                    $data = json_decode($response, true);
                    if (is_array($data) && isset($data['country'])) {
                        return $data;
                    }
                }
            } catch (\Throwable $e) {
                // Geo-IP failure is not critical
            }
            return [];
        });
    }
}
