<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Cles attendues avec leurs valeurs par defaut et types.
     */
    private const array DEFAULTS = [
        'withdrawal_fee_percent' => ['value' => '20', 'type' => 'integer'],
        'min_withdrawal_amount'  => ['value' => '1000', 'type' => 'integer'],
        'min_deposit_amount'     => ['value' => '1000', 'type' => 'integer'],
        'ugc_studio_fee_percent' => ['value' => '15', 'type' => 'integer'],
        'min_cpc_price'          => ['value' => '25', 'type' => 'integer'],
        'platform_commission_rate' => ['value' => '20', 'type' => 'integer'],
        'deposit_markup_percent' => ['value' => '1.5', 'type' => 'float'],
        'campaign_commission_percent' => ['value' => '5', 'type' => 'float'],
        'fedapay_fee_percent'    => ['value' => '1.5', 'type' => 'float'],
        'paydunya_fee_percent'   => ['value' => '2.0', 'type' => 'float'],
        // ── Paliers de campagne ──
        'tier_argent_threshold'  => ['value' => '25000', 'type' => 'integer'],
        'tier_or_threshold'      => ['value' => '100000', 'type' => 'integer'],
        'tier_cost_bronze'       => ['value' => '2000', 'type' => 'integer'],
        'tier_cost_argent'       => ['value' => '5000', 'type' => 'integer'],
        'tier_cost_or'           => ['value' => '15000', 'type' => 'integer'],
        'tier_followers_bronze_min' => ['value' => '1000', 'type' => 'integer'],
        'tier_followers_bronze_max' => ['value' => '9999', 'type' => 'integer'],
        'tier_followers_argent_min' => ['value' => '10000', 'type' => 'integer'],
        'tier_followers_argent_max' => ['value' => '99999', 'type' => 'integer'],
        'tier_followers_or_min'     => ['value' => '100000', 'type' => 'integer'],
        'tier_followers_or_max'     => ['value' => '10000000', 'type' => 'integer'],
        // ── Videos YouTube ──
        'video_vendor_guide'     => ['value' => '', 'type' => 'string'],
        'video_influencer_guide' => ['value' => '', 'type' => 'string'],
        'video_buyer_guide'      => ['value' => '', 'type' => 'string'],
        'video_welcome'          => ['value' => '', 'type' => 'string'],
        // Moderation
        'banned_keywords'        => ['value' => '[]', 'type' => 'json'],
        // Entreprise
        'company_name'           => ['value' => 'MANTOTA', 'type' => 'string'],
        'contact_email'          => ['value' => 'contact@mantota.com', 'type' => 'string'],
        'whatsapp_phone'         => ['value' => '+229 97 00 00 00', 'type' => 'string'],
        'rccm'                   => ['value' => '', 'type' => 'string'],
        'ifu'                    => ['value' => '', 'type' => 'string'],
        'physical_address'       => ['value' => '', 'type' => 'string'],
        // Reseaux sociaux
        'social_facebook'        => ['value' => '', 'type' => 'string'],
        'social_instagram'       => ['value' => '', 'type' => 'string'],
        'social_tiktok'          => ['value' => '', 'type' => 'string'],
        'social_twitter'         => ['value' => '', 'type' => 'string'],
        // Popups de bienvenue
        'welcome_popup_vendor'      => ['value' => 'Bienvenue sur MANTOTA ! En tant que vendeur, vous devez respecter le reglement de la plateforme : ne publiez pas de contenu inapproprie, respectez les delais de livraison, et maintenez une communication professionnelle avec les createurs de contenu. Tout manquement pourra entrainer la suspension de votre compte.', 'type' => 'string'],
        'welcome_popup_influencer'  => ['value' => 'Bienvenue sur MANTOTA ! Renseignez vos vraies informations et ne mentez pas sur le nombre de vos abonnes. Si nous controlons et remarquons que vous avez menti, vous risquez d\'etre banni de la plateforme. Vos abonnes comptent, mais la reaction de votre audience peut decider si vous etes classe dans la categorie Or, Argent ou Bronze.', 'type' => 'string'],
        // ── CMS : Page d'accueil ──
        'home_hero_title'        => ['value' => 'Vendez plus, plus vite.', 'type' => 'string'],
        'home_hero_subtitle'     => ['value' => 'MANTOTA est un reseau publicitaire 100% performance qui connecte vendeurs et createurs de contenu. Creez des campagnes, generez des liens partageables et suivez chaque conversion.', 'type' => 'string'],
        'home_step1_title'       => ['value' => 'Inscrivez-vous', 'type' => 'string'],
        'home_step1_desc'        => ['value' => 'Creez votre compte vendeur ou createur de contenu en quelques minutes. C\'est gratuit et sans engagement.', 'type' => 'string'],
        'home_step2_title'       => ['value' => 'Lancez ou partagez', 'type' => 'string'],
        'home_step2_desc'        => ['value' => 'Les vendeurs creent des campagnes CPC, les createurs de contenu partagent les liens et generent des clics qualifies.', 'type' => 'string'],
        'home_step3_title'       => ['value' => 'Gagnez & Grandissez', 'type' => 'string'],
        'home_step3_desc'        => ['value' => 'Chaque clic valide est remunere. Suivez vos performances en temps reel et retirez vos gains facilement.', 'type' => 'string'],
        'home_vendor_title'      => ['value' => 'Pour les Vendeurs', 'type' => 'string'],
        'home_vendor_desc'       => ['value' => 'Propulsez vos ventes grace au marketing d\'influence. Creez des campagnes CPC, suivez chaque clic en temps reel et ne payez que pour les resultats.', 'type' => 'string'],
        'home_vendor_image'      => ['value' => '', 'type' => 'string'],
        'home_influencer_title'  => ['value' => 'Pour les Createurs de Contenu', 'type' => 'string'],
        'home_influencer_desc'   => ['value' => 'Monetisez votre audience en partageant des liens de campagnes. Chaque clic genere vous rapporte de l\'argent, avec un suivi transparent.', 'type' => 'string'],
        'home_influencer_image'  => ['value' => '', 'type' => 'string'],
        'home_hero_image'        => ['value' => '', 'type' => 'string'],
        // ── CMS : Page A propos ──
        'about_mission'          => ['value' => 'MANTOTA est ne de la volonte de creer un pont entre les vendeurs et les createurs de contenu en Afrique. Notre mission est de democratiser le marketing d\'influence en le rendant accessible, transparent et base sur la performance reelle.', 'type' => 'string'],
        'about_why'              => ['value' => 'Nous croyons que chaque vendeur merite d\'acceder a un marketing efficace, et chaque createur de contenu merite d\'etre remunere equitablement. MANTOTA elimine les intermediaires et cree un ecosysteme ou la performance est reine.', 'type' => 'string'],
        // ── CMS : Page Documentation ──
        'doc_vendor_intro'       => ['value' => 'Guide complet pour les vendeurs utilisant la plateforme MANTOTA.', 'type' => 'string'],
        'doc_influencer_intro'   => ['value' => 'Guide complet pour les createurs de contenu utilisant la plateforme MANTOTA.', 'type' => 'string'],
        'doc_general_intro'      => ['value' => 'Informations generales sur le fonctionnement de MANTOTA.', 'type' => 'string'],
        // ── Logo ──
        'site_logo_light'        => ['value' => '/images/logo-white.png', 'type' => 'string'],
        'site_logo_dark'         => ['value' => '/images/logo-dark.png', 'type' => 'string'],
        'logo_width'             => ['value' => '140', 'type' => 'integer'],
        'logo_height'            => ['value' => '40', 'type' => 'integer'],
        // ── Ambassadeurs ──
        'ambassador_badge_price'    => ['value' => '5000', 'type' => 'integer'],
        'ambassador_sale_enabled'   => ['value' => '0', 'type' => 'boolean'],
        'ambassador_subscription_duration' => ['value' => '30', 'type' => 'integer'],
        'ambassador_commission_discount'   => ['value' => '50', 'type' => 'integer'],
        'ambassador_min_sales'      => ['value' => '50', 'type' => 'integer'],
        'ambassador_min_clicks'     => ['value' => '1000', 'type' => 'integer'],
        'restricted_circle_multiplier' => ['value' => '1.5', 'type' => 'float'],
        // ── Parrainage ──
        'referral_enabled'          => ['value' => '1', 'type' => 'boolean'],
        'referral_bonus_amount'     => ['value' => '500', 'type' => 'integer'],
        'referral_transfer_threshold' => ['value' => '10000', 'type' => 'integer'],
        // ── Securite ──
        'admin_recovery_code'       => ['value' => '', 'type' => 'string'],
    ];

    public function index()
    {
        $existing = Setting::all()->keyBy('key')->map(fn ($s) => [
            'value' => $s->value,
            'type'  => $s->type,
        ])->toArray();

        // Fusionne : les valeurs existantes ecrasent les defauts
        $settings = array_merge(self::DEFAULTS, $existing);

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings'            => ['required', 'array'],
            'settings.*.value'    => ['nullable', 'string', 'max:5000'],
            'settings.*.type'     => ['required', 'string', 'in:string,integer,float,boolean,json'],
        ]);

        foreach ($validated['settings'] as $key => $data) {
            Setting::set($key, $data['value'], $data['type']);
        }

        return back()->with('success', 'Parametres mis a jour.');
    }

    public function uploadLogo(Request $request)
    {
        $validated = $request->validate([
            'logo_type'   => ['required', 'in:site_logo_light,site_logo_dark'],
            'logo'        => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'logo_width'  => ['nullable', 'integer', 'min:20', 'max:500'],
            'logo_height' => ['nullable', 'integer', 'min:20', 'max:200'],
        ]);

        $file = $request->file('logo');
        $ext = strtolower($file->guessExtension() ?: 'png');
        $allowed = ['png', 'jpg', 'jpeg', 'webp'];
        if (! in_array($ext, $allowed, true)) {
            return back()->withErrors(['logo' => 'Format non autorise.']);
        }
        $filename = $validated['logo_type'] . '_' . time() . '.' . $ext;
        $file->storeAs('public/images', $filename);
        $path = '/storage/images/' . $filename;

        Setting::set($validated['logo_type'], $path, 'string');

        if ($validated['logo_width']) {
            Setting::set('logo_width', (string) $validated['logo_width'], 'integer');
        }
        if ($validated['logo_height']) {
            Setting::set('logo_height', (string) $validated['logo_height'], 'integer');
        }

        Cache::forget('global_settings_inertia');

        return back()->with('success', 'Logo mis a jour avec succes.');
    }
}
