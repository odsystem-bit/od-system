<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Support\Facades\Cache;

class SecurityService
{
    // Seuils de detection brute force
    private const BRUTE_FORCE_THRESHOLD = 10;
    private const BRUTE_FORCE_WINDOW_MINUTES = 15;
    private const AUTO_BAN_DURATION_HOURS = 24;

    /**
     * Enregistre un evenement de securite.
     */
    public static function log(
        string $eventType,
        ?int $userId = null,
        ?string $email = null,
        ?string $guard = null,
        array $metadata = []
    ): SecurityEvent {
        $request = request();

        $event = SecurityEvent::create([
            'event_type' => $eventType,
            'user_id'    => $userId,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'email'      => $email,
            'user_agent' => mb_substr((string) $request->header('User-Agent'), 0, 500),
            'country'    => null,
            'guard'      => $guard,
            'metadata'   => ! empty($metadata) ? $metadata : null,
            'created_at' => now(),
        ]);

        // Verifier brute force apres un login echoue
        if ($eventType === 'login_failed') {
            static::checkBruteForce($request->ip());
        }

        return $event;
    }

    /**
     * Detecte les tentatives de brute force et bloque l'IP automatiquement.
     */
    private static function checkBruteForce(string $ip): void
    {
        $failedCount = SecurityEvent::where('ip_address', $ip)
            ->where('event_type', 'login_failed')
            ->where('created_at', '>=', now()->subMinutes(self::BRUTE_FORCE_WINDOW_MINUTES))
            ->count();

        if ($failedCount >= self::BRUTE_FORCE_THRESHOLD) {
            // Eviter de bannir en boucle — verifier si deja bloque
            if (BlockedIp::isBlocked($ip)) {
                return;
            }

            BlockedIp::create([
                'ip_address'  => $ip,
                'reason'      => "Auto-ban: {$failedCount} tentatives echouees en " . self::BRUTE_FORCE_WINDOW_MINUTES . ' minutes',
                'is_permanent' => false,
                'expires_at'  => now()->addHours(self::AUTO_BAN_DURATION_HOURS),
            ]);

            // Enregistrer l'evenement de detection
            static::log('brute_force_detected', metadata: [
                'failed_attempts' => $failedCount,
                'ban_duration'    => self::AUTO_BAN_DURATION_HOURS . 'h',
            ]);

            // Alerter les admins
            static::alertAdmins(
                'Brute Force Detecte',
                "L'IP {$ip} a ete automatiquement bloquee apres {$failedCount} tentatives de connexion echouees en " . self::BRUTE_FORCE_WINDOW_MINUTES . " minutes."
            );
        }
    }

    /**
     * Alerte tous les admins d'un evenement de securite critique.
     * Throttle : 1 alerte par type + IP par tranche de 10 minutes.
     */
    public static function alertAdmins(string $title, string $message): void
    {
        $cacheKey = 'security_alert_' . md5($title . $message);

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 600); // 10 minutes

        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new SecurityAlertNotification($title, $message));
            }
        } catch (\Throwable) {
            // Ne pas briser le flux en cas d'erreur notification
        }
    }

    /**
     * Statistiques de securite pour le dashboard.
     */
    public static function getStats(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        return [
            'failed_logins'   => SecurityEvent::byType('login_failed')->where('created_at', '>=', $since)->count(),
            'successful_logins' => SecurityEvent::byType('login_success')->where('created_at', '>=', $since)->count(),
            'brute_force'     => SecurityEvent::byType('brute_force_detected')->where('created_at', '>=', $since)->count(),
            'blocked_ips'     => BlockedIp::active()->count(),
            'suspicious'      => SecurityEvent::byType('suspicious_login')->where('created_at', '>=', $since)->count(),
            'webhook_suspect' => SecurityEvent::byType('webhook_suspicious')->where('created_at', '>=', $since)->count(),
        ];
    }
}
