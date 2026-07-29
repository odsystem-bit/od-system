<?php

declare(strict_types=1);

namespace App\Http\Controllers\Influencer;

use App\Enums\CampaignStatus;
use App\Enums\CampaignTier;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\SmartLink;
use App\Services\Campaign\CampaignManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;

/**
 * CampaignController — Actions côté Créateur de contenu.
 *
 * Responsabilités :
 *  • Lister les campagnes actives disponibles.
 *  • Générer un SmartLink unique (48 h) pour une campagne.
 *  • Soumettre l'URL de preuve (vidéo/post) avant expiration.
 *
 * Toute la logique métier est déléguée au CampaignManager.
 * Le contrôleur ne fait que valider, orchestrer et formater les réponses Inertia.
 */
class CampaignController extends Controller
{
    /** Mapping nom de pays (profil créateur de contenu) → code ISO */
    private const COUNTRY_TO_ISO = [
        'Benin'              => 'BJ',
        'Togo'               => 'TG',
        "Cote d'Ivoire"      => 'CI',
        'Senegal'            => 'SN',
        'Cameroun'           => 'CM',
        'Burkina Faso'       => 'BF',
        'Mali'               => 'ML',
        'Niger'              => 'NE',
        'Guinee'             => 'GN',
        'Gabon'              => 'GA',
        'Congo'              => 'CG',
        'RD Congo'           => 'CD',
        'Tchad'              => 'TD',
        'Centrafrique'       => 'CF',
        'Mauritanie'         => 'MR',
        'Djibouti'           => 'DJ',
        'Comores'            => 'KM',
        'Madagascar'         => 'MG',
        'Burundi'            => 'BI',
        'Rwanda'             => 'RW',
        'France'             => 'FR',
        'Canada'             => 'CA',
        'Maroc'              => 'MA',
        'Tunisie'            => 'TN',
    ];

    // ──────────────────────────────────────────────
    //  1. Liste des campagnes actives
    // ──────────────────────────────────────────────

    /**
     * Affiche les campagnes ACTIVE avec leur vendor associe.
     * Filtre par palier : le créateur de contenu ne voit que les campagnes
     * correspondant a son palier (ou les campagnes open_sea).
     * 
     * MISSION 1 : Charge aussi la relation has_generated_link pour diff catalogue vs mes liens.
     */
    public function index(): InertiaResponse
    {
        $user = auth()->user();

        // Calculer le total de followers du créateur de contenu
        $totalFollowers = (int) ($user->tiktok_followers ?? 0)
            + (int) ($user->instagram_followers ?? 0)
            + (int) ($user->facebook_followers ?? 0)
            + (int) ($user->youtube_followers ?? 0)
            + (int) ($user->snapchat_followers ?? 0);

        $influencerTier = CampaignTier::fromFollowers($totalFollowers);

        // Geo-filtering : ne montrer que les campagnes ciblant le pays du créateur de contenu
        $influencerCountryIso = null;
        if ($user->country) {
            $country = $user->country;
            // Si c'est deja un code ISO (2 lettres), l'utiliser directement
            if (strlen($country) === 2 && ctype_alpha($country)) {
                $influencerCountryIso = strtoupper($country);
            } else {
                $influencerCountryIso = self::COUNTRY_TO_ISO[$country] ?? null;
            }
        }

        $campaigns = Campaign::query()
            ->where('status', CampaignStatus::ACTIVE)
            ->where('remaining_budget', '>', 0)
            ->where(function ($q) use ($influencerTier) {
                $q->where('tier', $influencerTier->value)
                  ->orWhere('open_sea', true)
                  ->orWhereNull('tier');
            })
            ->when($influencerCountryIso, function ($q) use ($influencerCountryIso) {
                $q->where(function ($sub) use ($influencerCountryIso) {
                    $sub->whereJsonContains('target_country', $influencerCountryIso)
                        ->orWhereNull('target_country');
                });
            })
            ->with('vendor:id,name,email,business_name,shop_name,shop_logo_path')
            ->withExists([
                'smartLinks as has_generated_link' => function ($query) {
                    $query->where('influencer_id', auth()->id());
                },
            ])
            ->latest()
            ->paginate(15);

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    // ──────────────────────────────────────────────
    //  2. Génération d'un SmartLink
    // ──────────────────────────────────────────────

    /**
     * Génère un SmartLink pour le créateur de contenu connecté sur la campagne donnée.
     */
    public function generateLink(Campaign $campaign, CampaignManager $manager): RedirectResponse
    {
        try {
            $link = $manager->generateSmartLink(auth()->user(), $campaign);

            $publicUrl = url("/go/{$link->unique_hash}");

            return redirect()
                ->back()
                ->with('success', "Lien genere avec succes !")
                ->with('smart_link_url', $publicUrl)
                ->with('smart_link_hash', $link->unique_hash)
                ->with('smart_link_expires', $link->expires_at->format('d/m/Y H:i'));

        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->withErrors(['campaign' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────
    //  3. Soumission de l'URL de preuve
    // ──────────────────────────────────────────────

    /**
     * Enregistre l'URL de la publication (vidéo/post) du créateur de contenu.
     */
    public function submitProof(Request $request, SmartLink $link, CampaignManager $manager): RedirectResponse
    {
        // ── Validation des données entrantes ──
        $validated = $request->validate([
            'proof_url' => ['required', 'url'],
        ]);

        // ── Sécurité : vérifier que le lien appartient à l'utilisateur connecté ──
        if ((int) $link->influencer_id !== (int) auth()->id()) {
            abort(403, 'Ce lien ne vous appartient pas.');
        }

        try {
            $manager->submitProofUrl($link, $validated['proof_url']);

            return redirect()
                ->back()
                ->with('success', 'Preuve soumise avec succès !');

        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->withErrors(['proof_url' => $e->getMessage()]);
        }
    }
}
