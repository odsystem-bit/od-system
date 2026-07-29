<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    /**
     * Supprime le compte de l'utilisateur connecte (soft delete).
     * Les donnees restent accessibles par l'admin pour l'historique.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $guard = null;
        $user = null;

        foreach (['vendor', 'influencer'] as $g) {
            if (Auth::guard($g)->check()) {
                $guard = $g;
                $user = Auth::guard($g)->user();
                break;
            }
        }

        if (!$user) {
            abort(403);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Le mot de passe est incorrect.',
            ]);
        }

        AuditLogService::log('self_delete_account', 'User', $user->id,
            ['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value],
            ['deleted' => true]
        );

        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete(); // SoftDeletes — sets deleted_at

        $loginRoute = $guard === 'vendor' ? 'vendor.login' : 'influencer.login';

        return redirect()->route($loginRoute)
            ->with('status', 'Votre compte a ete supprime avec succes.');
    }
}
