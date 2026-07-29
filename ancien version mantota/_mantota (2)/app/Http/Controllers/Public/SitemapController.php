<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $urls = collect();

        // Static pages
        $urls->push(['loc' => $baseUrl . '/', 'changefreq' => 'daily', 'priority' => '1.0']);
        $urls->push(['loc' => $baseUrl . '/tarifs', 'changefreq' => 'monthly', 'priority' => '0.7']);
        $urls->push(['loc' => $baseUrl . '/a-propos', 'changefreq' => 'monthly', 'priority' => '0.7']);
        $urls->push(['loc' => $baseUrl . '/documentation', 'changefreq' => 'monthly', 'priority' => '0.6']);
        $urls->push(['loc' => $baseUrl . '/conditions-generales', 'changefreq' => 'yearly', 'priority' => '0.3']);
        $urls->push(['loc' => $baseUrl . '/politique-confidentialite', 'changefreq' => 'yearly', 'priority' => '0.3']);
        $urls->push(['loc' => $baseUrl . '/support/create', 'changefreq' => 'monthly', 'priority' => '0.4']);

        // Vendor shops
        $vendors = User::where('role', 'vendor')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->select('slug', 'updated_at')
            ->limit(5000)
            ->get();

        foreach ($vendors as $vendor) {
            $urls->push([
                'loc' => $baseUrl . '/shop/' . $vendor->slug,
                'lastmod' => $vendor->updated_at?->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ]);
        }

        // Influencer public profiles
        $influencers = User::where('role', 'influencer')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where('kyc_status', 'approved')
            ->select('slug', 'updated_at')
            ->limit(5000)
            ->get();

        foreach ($influencers as $influencer) {
            $urls->push([
                'loc' => $baseUrl . '/influencer/' . $influencer->slug,
                'lastmod' => $influencer->updated_at?->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ]);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
            if (! empty($url['lastmod'])) {
                $xml .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            }
            $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
