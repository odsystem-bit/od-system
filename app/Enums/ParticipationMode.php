<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * ParticipationMode — Mode de participation d'une campagne.
 *
 *  - OPEN             : Tous les createurs peuvent participer, sans limite de places.
 *  - LIMITED          : Places limitees, auto-calculees selon budget / cout moyen par participation.
 *  - AMBASSADORS_ONLY : Reserve aux ambassadeurs MANTOTA (is_ambassador = true), places limitees.
 */
enum ParticipationMode: string
{
    case OPEN             = 'open';
    case LIMITED          = 'limited';
    case AMBASSADORS_ONLY = 'ambassadors_only';

    /** Labels lisibles pour le front-end. */
    public function label(): string
    {
        return match ($this) {
            self::OPEN             => 'Ouvert a tous',
            self::LIMITED          => 'Places limitees',
            self::AMBASSADORS_ONLY => 'Ambassadeurs uniquement',
        };
    }

    /** Toutes les options pour le front-end. */
    public static function options(): array
    {
        return array_map(fn (self $m) => [
            'value' => $m->value,
            'label' => $m->label(),
        ], self::cases());
    }

    /** Liste des valeurs brutes. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
