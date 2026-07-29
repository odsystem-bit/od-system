<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ImpersonationController extends Controller
{
    public function start(User $user)
    {
        $adminId = Auth::guard('admin')->id();

        // Audit log : tracer l'impersonation
        AuditLogService::log(
            'impersonate_start',
            'User',
            $user->id,
            [],
            ['target_name' => $user->name, 'target_role' => $user->role?->value]
        );

        session(['admin_impersonating_id' => $adminId]);

        $guard = match ($user->role) {
            UserRole::VENDOR     => 'vendor',
            UserRole::INFLUENCER => 'influencer',
            default              => null,
        };

        if (! $guard) {
            abort(403, 'Impossible d\'impersonner cet utilisateur.');
        }

        Auth::guard($guard)->loginUsingId($user->id);

        $prefix = $guard === 'vendor' ? 'vendor' : 'influencer';

        // Inertia::location force un rechargement complet de la page
        // necessaire car on change de rootView (admin → vendor/influencer)
        return Inertia::location(route("{$prefix}.dashboard"));
    }

    public function stop(Request $request)
    {
        $adminId = session('admin_impersonating_id');

        if (! $adminId) {
            return Inertia::location(route('admin.dashboard'));
        }

        // Logout from vendor/influencer guards
        Auth::guard('vendor')->logout();
        Auth::guard('influencer')->logout();

        // Restore admin session
        Auth::guard('admin')->loginUsingId($adminId);

        $request->session()->forget('admin_impersonating_id');

        return Inertia::location(route('admin.dashboard'));
    }
}
