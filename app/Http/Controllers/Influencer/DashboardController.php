<?php

declare(strict_types=1);

namespace App\Http\Controllers\Influencer;

use App\Enums\CampaignStatus;
use App\Enums\CampaignTier;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\KycLog;
use App\Models\SmartLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * DashboardController — Tableau de bord du créateur de contenu MANTOTA.
 *
 * Responsabilites :
 *  - Afficher les campagnes disponibles triees par MANTOTA Rank.
 *  - Filtrer selon l'eligibilite plateformes (followers > 0).
 *  - Exclure les campagnes a budget epuise (remaining_budget <= 0).
 *  - Lister les SmartLinks generes par le créateur de contenu.
 *
 *  MANTOTA Rank :
 *   Score = (commission_percent * 10) + (click_price * 5) + (remaining_budget / 1000)
 */
class DashboardController extends Controller
{
    // ──────────────────────────────────────────────
    //  Constantes — Plateformes
    // ──────────────────────────────────────────────

    /** Mapping plateforme → colonne followers sur la table users */
    private const PLATFORM_COLUMNS = [
        'tiktok'    => 'tiktok_followers',
        'instagram' => 'instagram_followers',
        'facebook'  => 'facebook_followers',
        'youtube'   => 'youtube_followers',
        'snapchat'  => 'snapchat_followers',
    ];

    // ──────────────────────────────────────────────
    //  1. Campagnes disponibles — MANTOTA Rank
    // ──────────────────────────────────────────────

    /**
     * Liste les campagnes ACTIVE triees par MANTOTA Rank, filtrees
     * par eligibilite de plateforme et budget restant.
     *
     * Logique d'eligibilite (v2 — Moteur Ads) :
     *  - Le créateur de contenu doit avoir des followers > 0 sur AU MOINS UNE
     *    des plateformes ciblees par la campagne (champ `platforms` JSON).
     *  - Les campagnes legacy sans `platforms` restent visibles a tous.
     *  - Seules les campagnes avec `remaining_budget > 0` sont affichees.
     *
     * MANTOTA Rank :
     *  Score = (COALESCE(commission_percent, 0) * 10)
     *        + (COALESCE(click_price, 0) * 5)
     *        + (COALESCE(remaining_budget, 0) / 1000)
     */
    public function index(): InertiaResponse
    {
        $user   = auth()->user();
        $userId = (int) $user->id;

        // ── IDs des campagnes ou le créateur de contenu a deja un lien non expire ──
        $activeLinkCampaignIds = SmartLink::query()
            ->where('influencer_id', $userId)
            ->where('expires_at', '>', now())
            ->pluck('campaign_id')
            ->toArray();

        // ── Plateformes ou le créateur de contenu a des followers > 0 ──
        $activePlatforms = [];
        $totalFollowers  = 0;
        foreach (self::PLATFORM_COLUMNS as $platform => $column) {
            $count = (int) ($user->$column ?? 0);
            $totalFollowers += $count;
            if ($count > 0) {
                $activePlatforms[] = $platform;
            }
        }

        // ── Determine le palier du créateur de contenu selon ses followers ──
        $influencerTier = CampaignTier::fromFollowers($totalFollowers);

        // ── Niches du créateur de contenu (filtre ciblage) ──
        $influencerNiches = $user->niches ?? [];

        // ── Requete avec MANTOTA Rank + Filtre Palier ──
        $campaigns = Campaign::query()
            ->selectRaw(
                '*, '
                . '(COALESCE(commission_percent, 0) * 10) '
                . '+ (COALESCE(click_price, 0) * 5) '
                . '+ (COALESCE(remaining_budget, 0) / 1000) '
                . 'AS mantota_score'
            )
            ->where('status', CampaignStatus::ACTIVE)
            ->where('remaining_budget', '>', 0)
            ->whereNotIn('id', $activeLinkCampaignIds)
            // Filtre Niche — le créateur de contenu ne voit que les campagnes
            // ciblant l'une de ses niches, ou les campagnes legacy sans niche.
            ->where(function (Builder $q) use ($influencerNiches) {
                $q->whereNull('niche');

                if (! empty($influencerNiches)) {
                    $q->orWhereIn('niche', $influencerNiches);
                }
            })
            // Filtre plateforme — créateur de contenu doit disposer de followers
            // sur au moins UNE plateforme ciblee par la campagne
            ->where(function (Builder $q) use ($activePlatforms) {
                // Campagnes legacy sans plateformes definies → visibles par tous
                $q->whereNull('platforms');

                // Campagnes avec plateformes → au moins un match
                foreach ($activePlatforms as $platform) {
                    $q->orWhereJsonContains('platforms', $platform);
                }
            })
            // ── Filtre Palier MANTOTA ──
            // Un créateur de contenu ne voit QUE les campagnes de son palier,
            // SAUF si la campagne a l'option open_sea activee.
            // Les ambassadeurs voient aussi les campagnes restricted_circle
            // ou ambassadors_only, quel que soit le palier.
            ->where(function (Builder $q) use ($influencerTier, $user) {
                $q->where('tier', $influencerTier->value)
                  ->orWhere('open_sea', true)
                  ->orWhereNull('tier'); // Campagnes legacy sans palier

                if ($user->is_ambassador) {
                    $q->orWhere('restricted_circle', true)
                      ->orWhere('participation_mode', 'ambassadors_only');
                }
            })
            // ── Filtre Cercle Restreint ──
            // Les campagnes restricted_circle ne sont visibles qu'aux ambassadeurs.
            ->where(function (Builder $q) use ($user) {
                $q->where('restricted_circle', false)
                  ->orWhereNull('restricted_circle');

                if ($user->is_ambassador) {
                    $q->orWhere('restricted_circle', true);
                }
            })
            // ── Filtre Ambassadeurs Only ──
            // Les campagnes ambassadors_only ne sont visibles qu'aux ambassadeurs.
            ->where(function (Builder $q) use ($user) {
                $q->where('participation_mode', '!=', 'ambassadors_only')
                  ->orWhereNull('participation_mode');

                if ($user->is_ambassador) {
                    $q->orWhere('participation_mode', 'ambassadors_only');
                }
            })
            ->with('vendor:id,name,business_name,shop_name,shop_logo_path')
            ->withCount('smartLinks')
            ->orderByDesc('mantota_score')
            ->paginate(12);

        // Derniere raison de rejet KYC (pour popup front-end)
        $kycRejectionReason = null;
        if (($user->kyc_status ?? '') === 'rejected') {
            $lastReject = KycLog::where('user_id', $user->id)
                ->where('action', 'rejected')
                ->latest()
                ->first();
            $kycRejectionReason = $lastReject?->reason;
        }

        // ── Onboarding checklist ──
        $hasSocials = $totalFollowers >= 5000 && count($activePlatforms) > 0;
        $onboarding = [
            'email_verified' => $user->hasVerifiedEmail(),
            'socials_done'   => $hasSocials,
            'niches_done'    => ! empty($influencerNiches),
            'photo_done'     => ! empty($user->profile_photo),
            'kyc_submitted'  => ! in_array($user->kyc_status ?? 'not_submitted', ['not_submitted', 'rejected']),
        ];

        return Inertia::render('Dashboard', [
            'campaigns'            => $campaigns,
            'kyc_status'           => $user->kyc_status ?? 'not_submitted',
            'kyc_rejection_reason' => $kycRejectionReason,
            'has_niches'           => ! empty($influencerNiches),
            'is_ambassador'        => (bool) $user->is_ambassador,
            'socials_expired'      => $user->socials_updated_at
                ? $user->socials_updated_at->lt(now()->subMonths(2))
                : ($user->created_at?->lt(now()->subMonths(2)) ?? false),
            'socials_updated_at'   => $user->socials_updated_at?->toISOString(),
            'onboarding'           => $onboarding,
            'ambassadors'          => User::where('is_ambassador', true)->whereNotNull('name')->inRandomOrder()->limit(12)->get(['id', 'name', 'profile_photo', 'role', 'shop_name', 'business_name', 'shop_logo_path']),
            'referral'             => [
                'code'     => $user->referral_code,
                'count'    => (int) $user->referral_count,
                'earnings' => (float) $user->referral_earnings,
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    //  2. Mes liens generes
    // ──────────────────────────────────────────────

    /**
     * Liste les SmartLinks du créateur de contenu connecte
     * avec la campagne associee, le nombre de clics payes
     * et le click_price pour le calcul des gains cote frontend.
     */
    public function myLinks(): InertiaResponse
    {
        $links = SmartLink::query()
            ->where('influencer_id', auth()->id())
            ->with('campaign:id,title,target_url,status,click_price,media_path,media_type,instructions')
            ->withCount(['clickLogs as paid_clicks_count' => function (Builder $q) {
                $q->where('is_paid', true);
            }])
            ->withCount(['clickLogs as total_clicks_count'])
            ->withCount(['clickLogs as invalid_clicks_count' => function (Builder $q) {
                $q->where('is_valid', false);
            }])
            ->latest()
            ->paginate(15);

        return Inertia::render('MyLinks', [
            'links' => $links,
        ]);
    }
}
