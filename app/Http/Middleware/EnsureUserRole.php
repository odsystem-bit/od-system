<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureUserRole — Verrouillage des routes par role utilisateur.
 *
 * Usage dans les routes :
 *   ->middleware('role:vendor')
 *   ->middleware('role:influencer')
 *   ->middleware('role:vendor,admin')
 *
 * Compare le champ `role` (cast en UserRole enum) de l'utilisateur connecte
 * avec la liste de roles autorises passes en parametre.
 */
class EnsureUserRole
{
    /**
     * @param  Closure(Request): Response  $next
     * @param  string ...$roles  Roles autorises (values de l'enum UserRole).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Detecter le guard selon le prefixe de route
        $guard = match (true) {
            $request->is('admin/*'), $request->is('admin')           => 'admin',
            $request->is('vendor/*'), $request->is('vendor')         => 'vendor',
            $request->is('influencer/*'), $request->is('influencer') => 'influencer',
            default => null,
        };
        $user = $request->user($guard);

        if (! $user || ! in_array($user->role?->value, $roles, true)) {
            abort(403, 'Acces refuse : role insuffisant.');
        }

        return $next($request);
    }
}
