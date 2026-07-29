<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Partner;
use App\Models\ServiceOrder;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $stats = Cache::remember('home_stats', 300, function () {
            return [
                'vendors_count'          => User::where('role', 'vendor')->count(),
                'influencers_count'      => User::where('role', 'influencer')->count(),
                'active_campaigns_count' => Campaign::where('status', 'active')->count(),
                'active_ugc_orders'      => ServiceOrder::whereIn('status', ['pending', 'shooting', 'revision_requested'])->count(),
                'delivered_ugc_orders'   => ServiceOrder::where('status', 'completed')->count(),
                'total_buyers'           => Order::distinct('customer_phone')->count('customer_phone'),
            ];
        });

        return Inertia::render('WelcomeNew', array_merge($stats, [
            'seo' => [
                'title' => 'MANTOTA — Reseau publicitaire 100% Performance au Benin et en Afrique',
                'description' => 'MANTOTA — Premiere plateforme de marketing d\'influence en Afrique. Connectez vendeurs et créateurs de contenu pour booster vos ventes. Paiement securise par sequestre.',
                'keywords' => 'MANTOTA, marketing influence Afrique, vendeurs Benin, créateurs de contenu Afrique, publicite performance, e-commerce Benin, campagne marketing, reseau publicitaire',
                'jsonld' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => 'MANTOTA',
                    'url' => config('app.url'),
                    'description' => 'Reseau publicitaire 100% Performance au Benin et en Afrique.',
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => config('app.url') . '/shop/{search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
            'hero_title'    => mantota_setting('home_hero_title', 'Vendez plus, plus vite.'),
            'hero_subtitle' => mantota_setting('home_hero_subtitle', 'MANTOTA est un reseau publicitaire 100% performance qui connecte vendeurs et créateurs de contenu. Creez des campagnes, generez des liens partageables et suivez chaque conversion.'),
            'step1_title'   => mantota_setting('home_step1_title', 'Inscrivez-vous'),
            'step1_desc'    => mantota_setting('home_step1_desc', 'Creez votre compte vendeur ou créateur de contenu en quelques minutes. C\'est gratuit et sans engagement.'),
            'step2_title'   => mantota_setting('home_step2_title', 'Lancez ou partagez'),
            'step2_desc'    => mantota_setting('home_step2_desc', 'Les vendeurs creent des campagnes CPC, les créateurs de contenu partagent les liens et generent des clics qualifies.'),
            'step3_title'   => mantota_setting('home_step3_title', 'Gagnez & Grandissez'),
            'step3_desc'    => mantota_setting('home_step3_desc', 'Chaque clic valide est remunere. Suivez vos performances en temps reel et retirez vos gains facilement.'),
            'vendor_title'      => mantota_setting('home_vendor_title', 'Pour les Vendeurs'),
            'vendor_desc'       => mantota_setting('home_vendor_desc', 'Propulsez vos ventes grace au marketing d\'influence. Creez des campagnes CPC, suivez chaque clic en temps reel et ne payez que pour les resultats.'),
            'vendor_image'      => mantota_setting('home_vendor_image', ''),
            'influencer_title'  => mantota_setting('home_influencer_title', 'Pour les Créateurs de contenu'),
            'influencer_desc'   => mantota_setting('home_influencer_desc', 'Monetisez votre audience en partageant des liens de campagnes. Chaque clic genere vous rapporte de l\'argent, avec un suivi transparent.'),
            'influencer_image'  => mantota_setting('home_influencer_image', ''),
            'hero_image'        => mantota_setting('home_hero_image', ''),
            'testimonials'      => Testimonial::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'role', 'content', 'rating']),
            'ambassadors'       => User::where('is_ambassador', true)->whereNotNull('name')->inRandomOrder()->limit(12)->get(['id', 'name', 'profile_photo', 'role', 'shop_name', 'business_name', 'tier', 'shop_logo_path']),
            'partners'          => Partner::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'logo', 'url']),
            'video_vendor_guide'     => mantota_setting('video_vendor_guide', ''),
            'video_influencer_guide' => mantota_setting('video_influencer_guide', ''),
            'video_buyer_guide'      => mantota_setting('video_buyer_guide', ''),
            'video_welcome'          => mantota_setting('video_welcome', ''),
        ]));
    }
}
