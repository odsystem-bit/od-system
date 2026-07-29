<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Enums\CampaignStatus;
use App\Enums\ParticipationMode;
use App\Models\Campaign;
use App\Models\SmartLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * CampaignManager — Logique métier centrale des campagnes et SmartLinks.
 *
 * Responsabilités :
 *  - Générer un lien unique (SmartLink) pour un créateur de contenu sur une campagne active.
 *  - Soumettre l'URL de preuve (vidéo/post) avant l'expiration du délai de 48 h.
 *  - Traiter un clic entrant : résoudre le hash → rediriger vers la boutique du vendor.
 */
final class CampaignManager
{
    // ──────────────────────────────────────────────
    //  1. Génération du SmartLink (chronomètre 48 h)
    // ──────────────────────────────────────────────

    /**
     * Crée un SmartLink unique pour un créateur de contenu sur une campagne.
     *
     * Règles métier :
     *  • La campagne DOIT être ACTIVE.
     *  • Un créateur de contenu ne peut avoir qu'UN SEUL lien actif (non expiré)
     *    par campagne à un instant donné.
     *  • Le lien expire exactement 48 heures après sa création.
     *
     * @throws RuntimeException Si la campagne n'est pas active.
     * @throws RuntimeException Si le créateur de contenu possède déjà un lien actif pour cette campagne.
     */
    public function generateSmartLink(User $influencer, Campaign $campaign): SmartLink
    {
        // ── Garde : la campagne doit être active ──
        if ($campaign->status !== CampaignStatus::ACTIVE) {
            throw new RuntimeException(
                "Impossible de générer un lien : la campagne « {$campaign->title} » n'est pas active (statut actuel : {$campaign->status->value})."
            );
        }

        // ── Garde : mode ambassadeurs uniquement ──
        if ($campaign->participation_mode === ParticipationMode::AMBASSADORS_ONLY && ! $influencer->is_ambassador) {
            throw new RuntimeException(
                'Cette campagne est réservée aux ambassadeurs MANTOTA.'
            );
        }

        // ── Garde : places limitées (limited ou ambassadors_only) ──
        if (
            $campaign->participation_mode !== ParticipationMode::OPEN
            && $campaign->max_participants !== null
            && $campaign->current_participants >= $campaign->max_participants
        ) {
            throw new RuntimeException(
                'Cette campagne est complète. Toutes les places ont été prises.'
            );
        }

        // ── Garde : un seul lien actif par créateur de contenu / campagne ──
        $existingActiveLink = SmartLink::query()
            ->where('campaign_id', $campaign->id)
            ->where('influencer_id', $influencer->id)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingActiveLink !== null) {
            throw new RuntimeException(
                "Le créateur de contenu possède déjà un lien actif pour cette campagne (expire le {$existingActiveLink->expires_at->toDateTimeString()})."
            );
        }

        // ── Création du SmartLink + incrémentation atomique ──
        return DB::transaction(function () use ($campaign, $influencer) {
            // Verrouiller la campagne pour éviter les doublons sous concurrence
            $lockedCampaign = Campaign::where('id', $campaign->id)
                ->lockForUpdate()
                ->first();

            // Re-check capacité après verrouillage
            if (
                $lockedCampaign->participation_mode !== ParticipationMode::OPEN
                && $lockedCampaign->max_participants !== null
                && $lockedCampaign->current_participants >= $lockedCampaign->max_participants
            ) {
                throw new RuntimeException(
                    'Cette campagne est complète. Toutes les places ont été prises.'
                );
            }

            $link = SmartLink::create([
                'campaign_id'   => $lockedCampaign->id,
                'influencer_id' => $influencer->id,
                'unique_hash'   => Str::random(10),
                'expires_at'    => now()->addHours(48),
            ]);

            // Incrémenter le compteur de participants
            $lockedCampaign->increment('current_participants');

            return $link;
        });
    }

    // ──────────────────────────────────────────────
    //  2. Soumission de l'URL de preuve
    // ──────────────────────────────────────────────

    /**
     * Enregistre l'URL de la publication (vidéo/post) du créateur de contenu.
     *
     * @throws RuntimeException Si le délai de 48 h est expiré.
     */
    public function submitProofUrl(SmartLink $link, string $url): bool
    {
        if (! $link->isValid()) {
            throw new RuntimeException(
                'Le délai de 48h est expiré. Impossible de soumettre la preuve.'
            );
        }

        $link->proof_url = $url;

        return $link->save();
    }

    // ──────────────────────────────────────────────
    //  3. Traitement d'un clic entrant
    // ──────────────────────────────────────────────

    /**
     * Résout un hash de SmartLink et retourne la target_url de la campagne.
     *
     * Comportement :
     *  • Si le hash n'existe pas → retourne null.
     *  • Si le lien est encore valide → déclenche le tracking anti-fraude (TODO).
     *  • Dans TOUS les cas (valide ou expiré) → retourne la target_url du vendor
     *    pour que l'utilisateur soit toujours redirigé vers la boutique.
     */
    public function processClick(string $hash): ?string
    {
        $link = SmartLink::with('campaign')
            ->where('unique_hash', $hash)
            ->first();

        if ($link === null) {
            return null;
        }

        // Le traitement du clic (anti-fraude, facturation, credit créateur de contenu)
        // est gere par SmartLinkController@redirect en temps reel.
        return $link->campaign->target_url;
    }
}
