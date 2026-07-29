<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Niche — Categories de contenu pour le ciblage MANTOTA.
 *
 * Chaque créateur de contenu selectionne jusqu'a 3 niches dans son profil.
 * Chaque campagne cible exactement UNE niche.
 * Le moteur Ads filtre les campagnes selon les niches du créateur de contenu.
 */
enum Niche: string
{
    case BEAUTY   = 'beauty';
    case FASHION  = 'fashion';
    case TECH     = 'tech';
    case COMEDY   = 'comedy';
    case BUSINESS = 'business';
    case HEALTH   = 'health';
    case LIFESTYLE = 'lifestyle';

    /**
     * Label lisible en francais pour l'affichage frontend.
     */
    public function label(): string
    {
        return match ($this) {
            self::BEAUTY   => 'Beaute & Cosmetiques',
            self::FASHION  => 'Mode & Vetements',
            self::TECH     => 'Tech & Gadgets',
            self::COMEDY   => 'Humour & Divertissement',
            self::BUSINESS => 'Business & Argent',
            self::HEALTH   => 'Sante & Bien-etre',
            self::LIFESTYLE => 'Lifestyle & General',
        };
    }

    /**
     * Retourne toutes les niches sous forme de tableau [value => label].
     */
    public static function options(): array
    {
        return array_map(
            fn (self $niche) => [
                'value' => $niche->value,
                'label' => $niche->label(),
            ],
            self::cases(),
        );
    }

    /**
     * Retourne les valeurs brutes (pour les regles de validation).
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
