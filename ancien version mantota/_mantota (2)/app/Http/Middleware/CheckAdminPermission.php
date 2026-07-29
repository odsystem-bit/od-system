<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckAdminPermission — Verifie qu'un admin possede la permission requise.
 *
 * Usage dans les routes :
 *   ->middleware('admin.permission:manage_users')
 *   ->middleware('admin.permission:manage_finance')
 *
 * Les super_admin contournent toutes les permissions.
 * La colonne admin_permissions est un JSON array sur le modele User.
 */
class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $request->user('admin');

        if (! $admin) {
            abort(403, 'Acces refuse.');
        }

        $permissions = $admin->admin_permissions ?? [];

        // super_admin peut tout faire
        if (in_array('super_admin', $permissions, true)) {
            return $next($request);
        }

        if (! in_array($permission, $permissions, true)) {
            abort(403, 'Vous n\'avez pas la permission requise : ' . $permission);
        }

        return $next($request);
    }
}
