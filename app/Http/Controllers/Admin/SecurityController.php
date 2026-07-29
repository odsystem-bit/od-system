<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTrustedDevice;
use App\Models\BlockedIp;
use App\Models\SecurityEvent;
use App\Services\SecurityService;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SecurityController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $stats = SecurityService::getStats(24);

        // Derniers evenements de securite (paginés)
        $events = SecurityEvent::query()
            ->with('user:id,name,email')
            ->when($request->input('type'), fn ($q, $type) => $q->where('event_type', $type))
            ->when($request->input('ip'), fn ($q, $ip) => $q->where('ip_address', $ip))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        // IPs bloquees actives
        $blockedIps = BlockedIp::active()
            ->with('blocker:id,name')
            ->latest()
            ->get();

        // Top IPs avec le plus de tentatives echouees (24h)
        $topAttackers = SecurityEvent::where('event_type', 'login_failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('ip_address, COUNT(*) as attempts, MAX(created_at) as last_attempt')
            ->groupBy('ip_address')
            ->orderByDesc('attempts')
            ->limit(20)
            ->get();

        // Niveau de menace
        $threatLevel = $this->calculateThreatLevel($stats);

        // Appareils de confiance
        $trustedDevices = AdminTrustedDevice::with('user:id,name,email')
            ->latest()
            ->get();

        // ── Click Fraud Analytics (24h) ──
        $clickFraudStats = [
            'vpn_blocked'       => DB::table('click_logs')->where('is_vpn', true)->where('created_at', '>=', now()->subHours(24))->count(),
            'bots_blocked'      => DB::table('click_logs')->whereIn('invalid_reason', ['bot_detected', 'suspicious_timing'])->where('created_at', '>=', now()->subHours(24))->count(),
            'device_duplicates' => DB::table('click_logs')->where('invalid_reason', 'duplicate_device')->where('created_at', '>=', now()->subHours(24))->count(),
            'ip_duplicates'     => DB::table('click_logs')->where('invalid_reason', 'duplicate_ip')->where('created_at', '>=', now()->subHours(24))->count(),
            'geo_mismatches'    => DB::table('click_logs')->where('invalid_reason', 'geo_mismatch')->where('created_at', '>=', now()->subHours(24))->count(),
            'total_blocked'     => DB::table('click_logs')->where('is_valid', false)->where('created_at', '>=', now()->subHours(24))->count(),
            'total_valid'       => DB::table('click_logs')->where('is_valid', true)->where('created_at', '>=', now()->subHours(24))->count(),
            'unique_devices'    => DB::table('click_logs')->whereNotNull('device_id')->where('created_at', '>=', now()->subHours(24))->distinct('device_id')->count('device_id'),
        ];

        // Recent fraud clicks (last 30)
        $recentFraudClicks = DB::table('click_logs')
            ->where('is_valid', false)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['ip_address', 'device_id', 'user_agent_hash', 'clicker_country', 'is_vpn', 'invalid_reason', 'created_at']);

        return Inertia::render('Security/Index', [
            'stats'             => $stats,
            'events'            => $events,
            'blockedIps'        => $blockedIps,
            'topAttackers'      => $topAttackers,
            'threatLevel'       => $threatLevel,
            'filters'           => $request->only('type', 'ip'),
            'trustedDevices'    => $trustedDevices,
            'currentDeviceToken' => $request->cookie('admin_device_token') && AdminTrustedDevice::where('device_token', $request->cookie('admin_device_token'))->exists()
                ? $request->cookie('admin_device_token')
                : null,
            'currentIp'         => $request->ip(),
            'clickFraudStats'   => $clickFraudStats,
            'recentFraudClicks' => $recentFraudClicks,
        ]);
    }

    public function blockIp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ip_address'   => ['required', 'ip'],
            'reason'       => ['required', 'string', 'max:255'],
            'is_permanent' => ['boolean'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:8760'], // max 1 an
        ]);

        BlockedIp::updateOrCreate(
            ['ip_address' => $validated['ip_address']],
            [
                'reason'       => $validated['reason'],
                'is_permanent' => $validated['is_permanent'] ?? false,
                'blocked_by'   => auth('admin')->id(),
                'expires_at'   => ($validated['is_permanent'] ?? false)
                    ? null
                    : now()->addHours($validated['duration_hours'] ?? 24),
            ]
        );

        // Invalider le cache pour cette IP
        \Illuminate\Support\Facades\Cache::forget('ip_blocked:' . $validated['ip_address']);

        SecurityService::log('ip_blocked_manual', metadata: [
            'target_ip' => $validated['ip_address'],
            'reason'    => $validated['reason'],
        ]);

        return back()->with('success', 'IP ' . $validated['ip_address'] . ' bloquee.');
    }

    public function unblockIp(BlockedIp $blockedIp): RedirectResponse
    {
        $ip = $blockedIp->ip_address;
        $blockedIp->delete();

        \Illuminate\Support\Facades\Cache::forget('ip_blocked:' . $ip);

        SecurityService::log('ip_unblocked', metadata: ['target_ip' => $ip]);

        return back()->with('success', 'IP ' . $ip . ' debloquee.');
    }

    // ── Appareils de confiance ──

    public function addTrustedDevice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $token = Str::random(64);

        AdminTrustedDevice::create([
            'user_id'      => auth('admin')->id(),
            'device_token' => $token,
            'device_name'  => $validated['device_name'],
            'user_agent'   => Str::limit($request->userAgent() ?? '', 255),
            'ip_address'   => $request->ip(),
            'last_used_at' => now(),
        ]);

        SecurityService::log('trusted_device_added', metadata: [
            'device_name' => $validated['device_name'],
        ]);

        // Set the cookie on this browser so it becomes the trusted device
        return back()
            ->with('success', 'Appareil "' . $validated['device_name'] . '" ajouté.')
            ->withCookie(cookie(
                'admin_device_token', $token, 60 * 24 * 730, '/', null, true, true, false, 'Lax'
            ));
    }

    public function removeTrustedDevice(AdminTrustedDevice $device): RedirectResponse
    {
        $name = $device->device_name;
        $device->delete();

        SecurityService::log('trusted_device_removed', metadata: [
            'device_name' => $name,
        ]);

        return back()->with('success', 'Appareil "' . $name . '" retiré.');
    }

    private function calculateThreatLevel(array $stats): string
    {
        if ($stats['brute_force'] > 0 || $stats['blocked_ips'] > 5) {
            return 'critical';
        }
        if ($stats['failed_logins'] > 50 || $stats['suspicious'] > 0) {
            return 'warning';
        }
        if ($stats['failed_logins'] > 10) {
            return 'elevated';
        }
        return 'normal';
    }
}
