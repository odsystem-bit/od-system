<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminTrustedDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminIpWhitelist — Limite l'accès à l'interface admin par appareil de confiance.
 *
 * Acces accorde si :
 *  1. Aucun appareil de confiance configuré (pas de restriction), OU
 *  2. Le navigateur presente un cookie « admin_device_token » valide.
 */
class AdminIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        // Aucun appareil configuré → pas de restriction
        if (AdminTrustedDevice::count() === 0) {
            return $next($request);
        }

        // Appareil de confiance → OK
        $token = $request->cookie('admin_device_token');
        if ($token && AdminTrustedDevice::where('device_token', $token)->exists()) {
            return $this->touchDeviceAndProceed($request, $next);
        }

        abort(403, 'Accès refusé — appareil non reconnu. Connectez-vous depuis un appareil de confiance.');
    }

    /**
     * Met a jour last_used_at du device si le cookie est present, puis continue.
     */
    private function touchDeviceAndProceed(Request $request, Closure $next): Response
    {
        $token = $request->cookie('admin_device_token');
        if ($token) {
            AdminTrustedDevice::where('device_token', $token)
                ->update(['last_used_at' => now(), 'ip_address' => $request->ip()]);
        }

        return $next($request);
    }
}
