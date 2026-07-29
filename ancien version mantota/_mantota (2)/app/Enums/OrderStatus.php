<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * OrderStatus — Etats possibles d'une commande e-commerce MANTOTA.
 *
 *  - PENDING   : Commande enregistree, paiement simule OK, en attente de livraison.
 *  - SHIPPED   : Le vendeur a marque la commande comme expediee.
 *  - DELIVERED : Le client a confirme la reception (libere le sequestre).
 *  - DISPUTED  : Le client a ouvert un litige.
 *  - CANCELLED : Commande annulee.
 */
enum OrderStatus: string
{
    case PENDING   = 'pending';
    case SHIPPED   = 'shipped';
    case DELIVERED = 'delivered';
    case DISPUTED  = 'disputed';
    case DISPUTED_RESOLVED = 'disputed_resolved';
    case CANCELLED = 'cancelled';

    /**
     * Label lisible en francais.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'En attente',
            self::SHIPPED   => 'Expediee',
            self::DELIVERED => 'Livree',
            self::DISPUTED  => 'Litige',
            self::DISPUTED_RESOLVED => 'Litige resolu',
            self::CANCELLED => 'Annulee',
        };
    }

    /**
     * Couleur Tailwind pour les badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING   => 'amber',
            self::SHIPPED   => 'blue',
            self::DELIVERED => 'emerald',
            self::DISPUTED  => 'red',
            self::DISPUTED_RESOLVED => 'violet',
            self::CANCELLED => 'slate',
        };
    }
}
