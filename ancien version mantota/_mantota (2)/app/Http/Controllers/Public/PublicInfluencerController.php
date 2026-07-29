<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\ServiceOrder;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * PublicInfluencerController — Profil public d'un créateur de contenu.
 *
 * Affiche le profil complet d'un créateur de contenu avec:
 * - Compteur global d'abonnés
 * - Tier et statut VIP
 * - Statistiques de confiance
 * - Présence sur réseaux sociaux
 * - Galerie de campagnes passées
 *
 * Accessible publiquement par /influencer/{username}
 */
class PublicInfluencerController extends Controller
{
    /**
     * Affiche le profil public d'un créateur de contenu.
     */
    public function show(string $username): InertiaResponse
    {
        // Résoudre le créateur de contenu par son slug
        $influencer = User::where('slug', $username)
            ->where('role', 'influencer')
            ->firstOrFail();

        // Charger le wallet pour afficher les statistiques
        $influencer->load('wallet');

        // Obtenir les campagnes complétées pour la galerie
        $completedCampaigns = Campaign::where('influencer_id', $influencer->id)
            ->whereNotIn('status', ['paused', 'deleted', 'archived'])
            ->with('product:id,name,slug')
            ->orderByDesc('created_at')
            ->paginate(12);

        // Obtenir le nombre total de services UGC créés
        $servicesCount = $influencer->influencerServices()->count();

        // Obtenir les commandes complétées
        $completedOrders = ServiceOrder::where('influencer_id', $influencer->id)
            ->where('status', 'completed')
            ->count();

        // Statistiques de confiance
        $stats = [
            'total_followers' => $influencer->getTotalFollowersAttribute(),
            'tier' => $influencer->tier ?? 'bronze',
            'is_vip' => $influencer->is_vip,
            'completed_orders' => $completedOrders,
            'services_created' => $servicesCount,
            'trust_score' => $this->calculateTrustScore($influencer),
        ];

        // Réseaux sociaux
        $socials = [
            'tiktok' => [
                'url' => $influencer->tiktok_url,
                'followers' => $influencer->tiktok_followers,
            ],
            'instagram' => [
                'url' => $influencer->instagram_url,
                'followers' => $influencer->instagram_followers,
            ],
            'facebook' => [
                'url' => $influencer->facebook_url,
                'followers' => $influencer->facebook_followers,
            ],
            'youtube' => [
                'url' => $influencer->youtube_url,
                'followers' => $influencer->youtube_followers,
            ],
            'snapchat' => [
                'url' => $influencer->snapchat_url,
                'followers' => $influencer->snapchat_followers,
            ],
        ];

        return Inertia::render('Influencer/PublicProfile', [
            'influencer' => $influencer,
            'stats' => $stats,
            'socials' => array_filter($socials, fn ($s) => $s['followers'] > 0),
            'campaigns' => $completedCampaigns,
        ]);
    }

    /**
     * Calcule un score de confiance pour le créateur de contenu.
     * Basé sur le nombre de commandes complétées et le statut.
     */
    private function calculateTrustScore(User $user): float
    {
        // Score de base selon le tier
        $baseScore = match ($user->tier) {
            'or' => 0.95,
            'argent' => 0.85,
            'bronze' => 0.70,
            default => 0.50,
        };

        // Ajustement VIP
        if ($user->is_vip) {
            $baseScore += 0.05;
        }

        // Capped at 1.0
        return min($baseScore, 1.0);
    }
}
