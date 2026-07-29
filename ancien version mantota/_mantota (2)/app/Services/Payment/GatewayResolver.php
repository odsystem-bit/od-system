<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Gateway;
use Illuminate\Support\Collection;

/**
 * Sélectionne la meilleure passerelle de paiement pour un pays donné.
 *
 * Logique :
 *  1. Filtrer les passerelles actives dont le JSON `countries` contient le code ISO.
 *  2. Trier par `priority` ASC (1 = prioritaire), puis `payin_fee` ASC.
 *  3. Retourner la première correspondance.
 *
 * Si aucun pays n'est fourni (ex: produit digital), on prend
 * la première passerelle active triée par priorité.
 */
class GatewayResolver
{
    /**
     * Retourne la meilleure passerelle pour un pays donné.
     */
    public static function resolve(?string $countryCode = null): ?Gateway
    {
        $gateways = self::allForCountry($countryCode);

        return $gateways->first();
    }

    /**
     * Retourne toutes les passerelles actives couvrant un pays, triées par priorité.
     */
    public static function allForCountry(?string $countryCode = null): Collection
    {
        $query = Gateway::where('is_active', true);

        $all = $query->orderBy('priority')->orderBy('payin_fee')->get();

        if (! $countryCode) {
            return $all;
        }

        // Normaliser : convertir le nom complet en code ISO si necessaire
        $code = self::normalizeCountryCode($countryCode);

        $filtered = $all->filter(fn (Gateway $gw) => $gw->coversCountry($code))->values();

        // Fallback : si aucune passerelle ne couvre ce pays, retourner toutes les actives
        return $filtered->isNotEmpty() ? $filtered : $all;
    }

    /**
     * Normalise un code ou nom de pays en code ISO 2 lettres.
     */
    public static function normalizeCountryCode(string $input): string
    {
        $upper = strtoupper(trim($input));

        // Si c'est deja un code ISO (2 lettres), le retourner
        if (strlen($upper) === 2) {
            return $upper;
        }

        // Map des noms complets vers codes ISO
        return match (true) {
            str_contains($upper, 'BENIN') || str_contains($upper, 'BÉNIN') => 'BJ',
            str_contains($upper, 'TOGO') => 'TG',
            str_contains($upper, 'IVOIRE') || str_contains($upper, 'IVORY') || $upper === 'CI' => 'CI',
            str_contains($upper, 'SENEGAL') || str_contains($upper, 'SÉNÉGAL') => 'SN',
            str_contains($upper, 'BURKINA') => 'BF',
            str_contains($upper, 'MALI') => 'ML',
            str_contains($upper, 'CAMEROUN') || str_contains($upper, 'CAMEROON') => 'CM',
            str_contains($upper, 'GUINEE') || str_contains($upper, 'GUINÉE') || str_contains($upper, 'GUINEA') => 'GN',
            str_contains($upper, 'NIGER') && ! str_contains($upper, 'NIGERIA') => 'NE',
            str_contains($upper, 'NIGERIA') => 'NG',
            str_contains($upper, 'GHANA') => 'GH',
            str_contains($upper, 'KENYA') => 'KE',
            str_contains($upper, 'BRAZZAVILLE') => 'CG',
            str_contains($upper, 'CONGO') && str_contains($upper, 'DEM') => 'CD',
            str_contains($upper, 'CONGO') => 'CG',
            str_contains($upper, 'RWANDA') => 'RW',
            default => $upper,
        };
    }

    /**
     * Map des devises par code pays.
     */
    public static function currencyForCountry(string $countryCode): string
    {
        return match (strtoupper($countryCode)) {
            'BJ', 'CI', 'SN', 'TG', 'BF', 'ML', 'GN', 'NE' => 'XOF',
            'CM', 'GA', 'CG', 'TD', 'CF', 'GQ' => 'XAF',
            'CD' => 'CDF',
            'NG' => 'NGN',
            'GH' => 'GHS',
            'KE' => 'KES',
            'ZA' => 'ZAR',
            'TZ' => 'TZS',
            'UG' => 'UGX',
            'RW' => 'RWF',
            default => 'XOF',
        };
    }
}
