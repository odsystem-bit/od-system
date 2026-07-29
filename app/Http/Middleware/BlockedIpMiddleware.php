<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BlockedIpMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Cache le resultat pendant 60 secondes pour eviter les requetes DB a chaque hit
        $isBlocked = Cache::remember(
            'ip_blocked:' . $ip,
            60,
            fn () => BlockedIp::isBlocked($ip)
        );

        if ($isBlocked) {
            abort(403, 'Votre adresse IP a ete temporairement bloquee suite a une activite suspecte.');
        }

        return $next($request);
    }
}
