<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AdminTrustedDevice;
use App\Services\SecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * AuthController — Authentification dediee au panel administrateur MANTOTA.
 *
 * Ce controleur est completement separe du flux utilisateur standard.
 * Seuls les comptes ayant le role UserRole::ADMIN peuvent se connecter ici.
 */
class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion administrateur (theme sombre).
     */
    public function create(): InertiaResponse|RedirectResponse
    {
        // Si deja connecte sur le guard admin, rediriger directement
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->role === UserRole::ADMIN) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Authentifie un administrateur.
     *
     * Securite :
     *  1. Validation email + password.
     *  2. Tentative d'authentification sur le guard « admin ».
     *  3. Verification que le compte est bien un ADMIN.
     *     Si ce n'est pas le cas → deconnexion immediate + erreur.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Tentative d'authentification sur le guard admin
        if (! Auth::guard('admin')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            SecurityService::log('login_failed', email: $request->input('email'), guard: 'admin');

            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        // Verification du role ADMIN
        $user = Auth::guard('admin')->user();

        if ($user->role !== UserRole::ADMIN) {
            // Deconnexion immediate — ce n'est pas un admin
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            SecurityService::log('unauthorized_access', $user->id, $user->email, 'admin', [
                'reason' => 'Non-admin attempted admin login',
            ]);

            throw ValidationException::withMessages([
                'email' => 'Acces refuse. Ce portail est reserve aux administrateurs.',
            ]);
        }

        $request->session()->regenerate();

        SecurityService::log('login_success', $user->id, $user->email, 'admin');

        // Auto-trust current device if no valid cookie
        $response = redirect()->route('admin.dashboard');

        $existingToken = $request->cookie('admin_device_token');
        $hasValidDevice = $existingToken && AdminTrustedDevice::where('device_token', $existingToken)->exists();

        if (! $hasValidDevice) {
            $token = Str::random(64);
            $ua    = $request->userAgent() ?? 'Unknown';

            AdminTrustedDevice::create([
                'user_id'      => $user->id,
                'device_token' => $token,
                'device_name'  => $this->guessDeviceName($ua),
                'user_agent'   => Str::limit($ua, 255),
                'ip_address'   => $request->ip(),
                'last_used_at' => now(),
            ]);

            // Cookie httpOnly, 2 ans
            $response = $response->withCookie(cookie(
                'admin_device_token', $token, 60 * 24 * 730, '/', null, true, true, false, 'Lax'
            ));
        }

        return $response;
    }

    /**
     * Deconnecte l'administrateur et redirige vers la page de login admin.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Devine un nom lisible à partir du User-Agent.
     */
    private function guessDeviceName(string $ua): string
    {
        $os = match (true) {
            str_contains($ua, 'Windows')    => 'Windows',
            str_contains($ua, 'Macintosh')  => 'Mac',
            str_contains($ua, 'Linux')      => 'Linux',
            str_contains($ua, 'Android')    => 'Android',
            str_contains($ua, 'iPhone')     => 'iPhone',
            str_contains($ua, 'iPad')       => 'iPad',
            default                         => 'Appareil',
        };

        $browser = match (true) {
            str_contains($ua, 'Edg/')     => 'Edge',
            str_contains($ua, 'Chrome/')  => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/')  => 'Safari',
            default                       => 'Navigateur',
        };

        return "$os — $browser";
    }
}
