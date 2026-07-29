<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * IpRecoveryController — Recuperation d'acces admin quand l'IP publique change.
 *
 * Route publique (hors middleware admin.ip) protegee par :
 *  - Code secret configurable depuis le panneau admin.
 *  - Rate limiting strict (5 tentatives / 15 min par IP).
 *  - Logging securite de chaque tentative.
 */
class IpRecoveryController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.ip-recovery', [
            'currentIp' => $request->ip(),
        ]);
    }

    public function recover(Request $request): RedirectResponse
    {
        $request->validate([
            'recovery_code' => ['required', 'string', 'max:100'],
        ]);

        $ip = $request->ip();
        $rateLimitKey = 'ip-recovery:' . $ip;

        // Rate limiting: 5 tentatives par 15 minutes
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            SecurityService::log('ip_recovery_rate_limited', metadata: [
                'ip' => $ip,
            ]);

            return back()->withErrors([
                'recovery_code' => 'Trop de tentatives. Reessayez dans ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        RateLimiter::hit($rateLimitKey, 900); // 15 min

        $storedCode = Setting::get('admin_recovery_code', '');

        if ($storedCode === '' || $request->input('recovery_code') !== $storedCode) {
            SecurityService::log('ip_recovery_failed', metadata: [
                'ip' => $ip,
            ]);

            return back()->withErrors([
                'recovery_code' => 'Code de recuperation incorrect.',
            ]);
        }

        // Code correct — ajouter l'IP a la whitelist
        $whitelist = Setting::get('admin_ip_whitelist', []);
        if (! is_array($whitelist)) {
            $whitelist = [];
        }

        if (! in_array($ip, $whitelist, true)) {
            $whitelist[] = $ip;
            Setting::set('admin_ip_whitelist', $whitelist, 'json');
        }

        RateLimiter::clear($rateLimitKey);

        SecurityService::log('ip_recovery_success', metadata: [
            'ip' => $ip,
        ]);

        return redirect()->route('admin.login')
            ->with('success', 'IP ' . $ip . ' ajoutee. Vous pouvez maintenant vous connecter.');
    }
}
