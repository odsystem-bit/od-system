<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * CampaignStatus — États possibles d'une campagne publicitaire.
 *
 *  - DRAFT   : Brouillon, la campagne n'est pas encore publiée.
 *  - ACTIVE  : En cours de diffusion (compteur de 48 h actif).
 *  - PAUSED  : Mise en pause manuelle par le vendor ou l'admin.
 *  - EXPIRED : Expirée automatiquement après 48 h ou budget épuisé.
 */
enum CampaignStatus: string
{
    case DRAFT     = 'draft';
    case ACTIVE    = 'active';
    case PAUSED    = 'paused';
    case COMPLETED = 'completed';
    case DELETED   = 'deleted';
    case EXPIRED   = 'expired';
    case REJECTED  = 'rejected';
}
