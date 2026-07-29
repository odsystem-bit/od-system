<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * CampaignTier — Paliers de visibilite des campagnes MANTOTA.
 *
 * Le palier determine quels créateurs de contenu voient la campagne :
 *  - BRONZE : Budget < 25 000 FCFA  → Nano (1k - 10k abonnes)
 *  - ARGENT : Budget 25k - 100k     → Micro / Macro (10k - 100k)
 *  - OR     : Budget > 100 000      → Elite / Stars (100k - 1M+)
 *
 * Cout moyen estime par participation :
 *  - BRONZE : 2 000 FCFA
 *  - ARGENT : 5 000 FCFA
 *  - OR     : 15 000 FCFA
 */
enum CampaignTier: string
{
    case BRONZE = 'bronze';
    case ARGENT = 'argent';
    case OR     = 'or';

    // ──────────────────────────────────────────────
    //  Seuils par defaut (FCFA) — Configurables via Admin > Parametres
    // ──────────────────────────────────────────────

    /** Seuil par defaut pour le palier ARGENT */
    public const DEFAULT_ARGENT_THRESHOLD = 25_000;

    /** Seuil par defaut pour le palier OR */
    public const DEFAULT_OR_THRESHOLD = 100_000;

    /** Cout par defaut par participation par palier */
    public const DEFAULT_COST_PER_PARTICIPATION = [
        'bronze' => 2_000,
        'argent' => 5_000,
        'or'     => 15_000,
    ];

    /** Marges d'abonnes par defaut par palier */
    public const DEFAULT_FOLLOWER_RANGES = [
        'bronze' => ['min' => 1_000,   'max' => 10_000],
        'argent' => ['min' => 10_000,  'max' => 100_000],
        'or'     => ['min' => 100_000, 'max' => 1_000_000],
    ];

    // ──────────────────────────────────────────────
    //  Accesseurs dynamiques (lisent les settings)
    // ──────────────────────────────────────────────

    public static function argentThreshold(): int
    {
        return (int) mantota_setting('tier_argent_threshold', self::DEFAULT_ARGENT_THRESHOLD);
    }

    public static function orThreshold(): int
    {
        return (int) mantota_setting('tier_or_threshold', self::DEFAULT_OR_THRESHOLD);
    }

    public static function costPerParticipation(): array
    {
        return [
            'bronze' => (int) mantota_setting('tier_cost_bronze', self::DEFAULT_COST_PER_PARTICIPATION['bronze']),
            'argent' => (int) mantota_setting('tier_cost_argent', self::DEFAULT_COST_PER_PARTICIPATION['argent']),
            'or'     => (int) mantota_setting('tier_cost_or', self::DEFAULT_COST_PER_PARTICIPATION['or']),
        ];
    }

    /**
     * Multiplicateur Cercle Restreint (campagnes reservees aux ambassadeurs).
     */
    public static function restrictedCircleMultiplier(): float
    {
        return (float) mantota_setting('restricted_circle_multiplier', '1.5');
    }

    public static function followerRanges(): array
    {
        return [
            'bronze' => [
                'min' => (int) mantota_setting('tier_followers_bronze_min', self::DEFAULT_FOLLOWER_RANGES['bronze']['min']),
                'max' => (int) mantota_setting('tier_followers_bronze_max', self::DEFAULT_FOLLOWER_RANGES['bronze']['max']),
            ],
            'argent' => [
                'min' => (int) mantota_setting('tier_followers_argent_min', self::DEFAULT_FOLLOWER_RANGES['argent']['min']),
                'max' => (int) mantota_setting('tier_followers_argent_max', self::DEFAULT_FOLLOWER_RANGES['argent']['max']),
            ],
            'or' => [
                'min' => (int) mantota_setting('tier_followers_or_min', self::DEFAULT_FOLLOWER_RANGES['or']['min']),
                'max' => (int) mantota_setting('tier_followers_or_max', self::DEFAULT_FOLLOWER_RANGES['or']['max']),
            ],
        ];
    }

    // ──────────────────────────────────────────────
    //  Resolution du palier depuis le budget
    // ──────────────────────────────────────────────

    /**
     * Determine le palier en fonction du budget total de la campagne.
     */
    public static function fromBudget(float $totalBudget): self
    {
        if ($totalBudget >= self::orThreshold()) {
            return self::OR;
        }

        if ($totalBudget >= self::argentThreshold()) {
            return self::ARGENT;
        }

        return self::BRONZE;
    }

    /**
     * Determine le palier d'un créateur de contenu selon son total de followers.
     */
    public static function fromFollowers(int $totalFollowers): self
    {
        $ranges = self::followerRanges();

        if ($totalFollowers >= $ranges['or']['min']) {
            return self::OR;
        }

        if ($totalFollowers >= $ranges['argent']['min']) {
            return self::ARGENT;
        }

        return self::BRONZE;
    }

    // ──────────────────────────────────────────────
    //  Labels & Config
    // ──────────────────────────────────────────────

    /** Label affichable */
    public function label(): string
    {
        return match ($this) {
            self::BRONZE => 'Bronze',
            self::ARGENT => 'Argent',
            self::OR     => 'Or',
        };
    }

    /** Type de créateur de contenu associe */
    public function influencerType(): string
    {
        return match ($this) {
            self::BRONZE => 'Nano',
            self::ARGENT => 'Micro / Macro',
            self::OR     => 'Elite / Stars',
        };
    }

    /** Marge d'abonnes formatee */
    public function followerRange(): string
    {
        $ranges = self::followerRanges();
        $r = $ranges[$this->value];
        return number_format($r['min'], 0, '', ' ') . ' - ' . ($r['max'] >= 1_000_000 ? '1M+' : number_format($r['max'], 0, '', ' '));
    }

    /** Cout moyen par participation */
    public function costPerParticipationValue(): int
    {
        return self::costPerParticipation()[$this->value];
    }

    /**
     * Estime le nombre de createurs potentiels.
     * Nombre = totalBudget / cout_moyen_par_tier
     */
    public function estimateCreators(float $totalBudget): int
    {
        $cost = $this->costPerParticipationValue();
        if ($cost <= 0) {
            return 0;
        }

        return (int) floor($totalBudget / $cost);
    }

    /**
     * Calcule l'incitation au palier superieur.
     * Retourne null si deja au palier max (OR) ou si le budget
     * est a moins de 80% du seuil du palier suivant.
     *
     * @return array{next_tier: self, remaining: float, threshold: float}|null
     */
    public static function upsellNudge(float $totalBudget): ?array
    {
        $orThreshold     = self::orThreshold();
        $argentThreshold = self::argentThreshold();

        if ($totalBudget >= $orThreshold) {
            return null;
        }

        $nextTier  = $totalBudget < $argentThreshold ? self::ARGENT : self::OR;
        $threshold = $nextTier === self::ARGENT ? $argentThreshold : $orThreshold;
        $ratio     = $totalBudget / $threshold;

        if ($ratio < 0.80) {
            return null;
        }

        return [
            'next_tier' => $nextTier,
            'remaining' => $threshold - $totalBudget,
            'threshold' => $threshold,
        ];
    }

    /**
     * Verifie si un créateur de contenu correspond au palier de la campagne.
     * Le créateur de contenu doit avoir un total de followers dans la marge du palier.
     */
    public function matchesInfluencer(int $totalFollowers): bool
    {
        $range = self::followerRanges()[$this->value];

        return $totalFollowers >= $range['min'];
    }

    /**
     * Retourne les donnees completes pour le frontend.
     */
    public static function allTiersData(): array
    {
        $costs  = self::costPerParticipation();
        $argent = self::argentThreshold();
        $or     = self::orThreshold();

        return [
            [
                'value'             => self::BRONZE->value,
                'label'             => self::BRONZE->label(),
                'influencer_type'   => self::BRONZE->influencerType(),
                'follower_range'    => self::BRONZE->followerRange(),
                'cost_participation' => $costs['bronze'],
                'min_budget'        => 0,
                'max_budget'        => $argent - 1,
            ],
            [
                'value'             => self::ARGENT->value,
                'label'             => self::ARGENT->label(),
                'influencer_type'   => self::ARGENT->influencerType(),
                'follower_range'    => self::ARGENT->followerRange(),
                'cost_participation' => $costs['argent'],
                'min_budget'        => $argent,
                'max_budget'        => $or - 1,
            ],
            [
                'value'             => self::OR->value,
                'label'             => self::OR->label(),
                'influencer_type'   => self::OR->influencerType(),
                'follower_range'    => self::OR->followerRange(),
                'cost_participation' => $costs['or'],
                'min_budget'        => $or,
                'max_budget'        => null,
            ],
        ];
    }
}
