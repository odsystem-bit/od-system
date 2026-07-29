<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CampaignTier;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function tarifs(): Response
    {
        return Inertia::render('Public/Tarifs', [
            'min_cpc'                => (int) mantota_setting('min_cpc_price', 25),
            'withdrawal_fee_percent' => (int) mantota_setting('withdrawal_fee_percent', 20),
            'min_withdrawal_amount'  => (int) mantota_setting('min_withdrawal_amount', 1000),
            'ugc_studio_fee_percent' => (int) mantota_setting('ugc_studio_fee_percent', 15),
            'tier_thresholds'        => [
                'argent' => CampaignTier::argentThreshold(),
                'or'     => CampaignTier::orThreshold(),
            ],
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('Public/About', [
            'about_mission' => mantota_setting('about_mission', 'MANTOTA est ne de la volonte de creer un pont entre les vendeurs et les créateurs de contenu en Afrique. Notre mission est de democratiser le marketing d\'influence en le rendant accessible, transparent et base sur la performance reelle.'),
            'about_why'     => mantota_setting('about_why', 'Nous croyons que chaque vendeur merite d\'acceder a un marketing efficace, et chaque créateur de contenu merite d\'etre remunere equitablement. MANTOTA elimine les intermediaires et cree un ecosysteme ou la performance est reine.'),
        ]);
    }

    public function documentation(): Response
    {
        return Inertia::render('Public/Documentation', [
            'doc_vendor_intro'      => mantota_setting('doc_vendor_intro', 'Guide complet pour les vendeurs utilisant la plateforme MANTOTA.'),
            'doc_influencer_intro'  => mantota_setting('doc_influencer_intro', 'Guide complet pour les créateurs de contenu utilisant la plateforme MANTOTA.'),
            'doc_general_intro'     => mantota_setting('doc_general_intro', 'Informations generales sur le fonctionnement de MANTOTA.'),
        ]);
    }

    public function docVendeur(): Response
    {
        return Inertia::render('Public/Documentation/Vendeur', [
            'min_cpc'                => (int) mantota_setting('min_cpc_price', 25),
            'min_deposit'            => (int) mantota_setting('min_deposit_amount', 1000),
            'min_withdrawal'         => (int) mantota_setting('min_withdrawal_amount', 1000),
            'ugc_fee'                => (int) mantota_setting('ugc_studio_fee_percent', 15),
        ]);
    }

    public function docCreateur(): Response
    {
        return Inertia::render('Public/Documentation/Createur', [
            'min_withdrawal'         => (int) mantota_setting('min_withdrawal_amount', 1000),
            'withdrawal_fee'         => (int) mantota_setting('withdrawal_fee_percent', 20),
        ]);
    }

    public function docClient(): Response
    {
        return Inertia::render('Public/Documentation/Client');
    }
}
