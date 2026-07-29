<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * UserRole — Rôles utilisateur de la plateforme.
 *
 *  - VENDOR      : Annonceur qui crée et finance les campagnes.
 *  - INFLUENCER  : Créateur de contenu qui diffuse les liens et perçoit les revenus.
 *  - ADMIN       : Administrateur plateforme avec accès complet.
 */
enum UserRole: string
{
    case VENDOR     = 'vendor';
    case INFLUENCER = 'influencer';
    case ADMIN      = 'admin';
}
