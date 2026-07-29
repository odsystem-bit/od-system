<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SystemErrorNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * SystemHealthController — Tableau de bord de santé du système MANTOTA.
 *
 * Vérifie : PHP, extensions, BDD, cache, stockage, disque, caches Laravel,
 * configuration .env critique, taille des logs, et liste les packages installés.
 * Alerte automatiquement les admins lors de problèmes critiques.
 */
class SystemHealthController extends Controller
{
    public function index(): InertiaResponse
    {
        $checks = $this->runChecks();
        $recentErrors = $this->getRecentErrors();
        $alertHistory = $this->getAlertHistory();

        return Inertia::render('SystemHealth/Index', [
            'checks'       => $checks,
            'recentErrors' => $recentErrors,
            'packages'     => $this->getInstalledPackages(),
            'logSizeKb'    => $this->getLogSizeKb(),
            'generatedAt'  => now()->toDateTimeString(),
            'alertHistory' => $alertHistory,
            'alertsEnabled' => (bool) Cache::get('system_alerts_enabled', true),
        ]);
    }

    /**
     * Toggle le système d'alertes automatiques.
     */
    public function toggleAlerts(): JsonResponse
    {
        $current = Cache::get('system_alerts_enabled', true);
        Cache::forever('system_alerts_enabled', !$current);

        return response()->json(['enabled' => !$current]);
    }

    /**
     * Vérification automatique (appelée par CRON ou manuellement).
     * Détecte les nouvelles erreurs et notifie tous les admins.
     */
    public function runAutoCheck(): JsonResponse
    {
        if (!Cache::get('system_alerts_enabled', true)) {
            return response()->json(['skipped' => true, 'reason' => 'Alerts disabled']);
        }

        $newErrors = $this->detectNewErrors();
        $criticalChecks = collect($this->runChecks())->where('status', 'critical');
        $alertsSent = 0;

        $admins = User::where('role', UserRole::ADMIN)->get();

        // Alert for critical system checks
        foreach ($criticalChecks as $check) {
            $cacheKey = 'alert_sent_check_' . $check['id'];
            if (!Cache::has($cacheKey)) {
                foreach ($admins as $admin) {
                    $admin->notify(new SystemErrorNotification(
                        'CRITICAL',
                        "Verification systeme echouee : {$check['label']} — {$check['value']}",
                        'SystemHealthController',
                        '0'
                    ));
                }
                Cache::put($cacheKey, true, 3600); // Don't re-alert for 1 hour
                $alertsSent++;
            }
        }

        // Alert for new log errors
        foreach ($newErrors as $error) {
            foreach ($admins as $admin) {
                $admin->notify(new SystemErrorNotification(
                    $error['level'],
                    $error['message'],
                    $error['file'] ?? 'unknown',
                    $error['line'] ?? '0'
                ));
            }
            $alertsSent++;
        }

        // Store alert history
        if ($alertsSent > 0) {
            $this->storeAlertRecord($alertsSent, count($newErrors), $criticalChecks->count());
        }

        return response()->json([
            'checked_at'    => now()->toDateTimeString(),
            'alerts_sent'   => $alertsSent,
            'new_errors'    => count($newErrors),
            'critical_checks' => $criticalChecks->count(),
        ]);
    }

    /**
     * Purge le fichier de log.
     */
    public function clearLog(): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return response()->json(['cleared' => true]);
    }

    // ──────────────────────────────────────────────
    //  Vérifications système
    // ──────────────────────────────────────────────

    private function runChecks(): array
    {
        $checks = [];

        // ── PHP Version ──
        $phpOk    = version_compare(PHP_VERSION, '8.2.0', '>=');
        $checks[] = $this->check(
            'php_version',
            'Version PHP',
            PHP_VERSION,
            $phpOk ? 'ok' : 'warning',
            $phpOk ? null : 'PHP 8.2+ recommandé'
        );

        // ── Extensions PHP requises ──
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'json', 'tokenizer', 'xml', 'ctype', 'fileinfo', 'gd'] as $ext) {
            $loaded   = extension_loaded($ext);
            $checks[] = $this->check(
                'ext_' . $ext,
                "Extension : {$ext}",
                $loaded ? 'Installée' : '⚠ Manquante',
                $loaded ? 'ok' : 'critical',
                $loaded ? null : "Activer {$ext} dans php.ini"
            );
        }

        // ── Connexion base de données ──
        try {
            DB::connection()->getPdo();
            $dbVer    = DB::selectOne('SELECT VERSION() AS v')?->v ?? '—';
            $checks[] = $this->check('db', 'Base de données', "Connectée ({$dbVer})", 'ok');
        } catch (\Throwable $e) {
            $checks[] = $this->check('db', 'Base de données', 'Erreur : ' . substr($e->getMessage(), 0, 80), 'critical', 'Vérifier DATABASE_URL dans .env');
        }

        // ── Cache ──
        try {
            Cache::put('_mantota_health_', 'ping', 10);
            $hit      = Cache::get('_mantota_health_') === 'ping';
            Cache::forget('_mantota_health_');
            $checks[] = $this->check('cache', 'Cache', $hit ? 'Fonctionnel' : 'Écriture impossible', $hit ? 'ok' : 'critical');
        } catch (\Throwable $e) {
            $checks[] = $this->check('cache', 'Cache', 'Erreur : ' . substr($e->getMessage(), 0, 60), 'critical');
        }

        // ── Storage accessible en écriture ──
        $storageOk = is_writable(storage_path());
        $checks[]  = $this->check(
            'storage',
            'Dossier storage/',
            $storageOk ? 'Accessible en écriture' : 'Lecture seule',
            $storageOk ? 'ok' : 'critical',
            $storageOk ? null : 'chmod 775 storage/ -R'
        );

        // ── Public storage accessible ──
        $publicOk = is_readable(public_path('storage'));
        $checks[]  = $this->check(
            'public_storage',
            'public/storage/',
            $publicOk ? 'Accessible' : 'Non accessible',
            $publicOk ? 'ok' : 'warning',
            $publicOk ? null : 'Le répertoire public/storage est manquant'
        );

        // ── Espace disque ──
        $free    = (float) disk_free_space(base_path());
        $total   = (float) disk_total_space(base_path());
        $usedPct = $total > 0 ? round((1 - $free / $total) * 100, 1) : 0;
        $freeGb  = round($free / 1073741824, 2);
        $totalGb = round($total / 1073741824, 2);
        $checks[] = $this->check(
            'disk',
            'Espace disque',
            "{$freeGb} GB libres / {$totalGb} GB ({$usedPct}% utilisé)",
            $usedPct > 90 ? 'critical' : ($usedPct > 75 ? 'warning' : 'ok'),
            $usedPct > 75 ? 'Libérer de l\'espace disque bientôt' : null
        );

        // ── Cache config ──
        $configCached = file_exists(base_path('bootstrap/cache/config.php'));
        $checks[]     = $this->check(
            'config_cache',
            'Cache config',
            $configCached ? 'Mis en cache' : 'Non mis en cache',
            $configCached ? 'ok' : 'warning',
            $configCached ? null : 'Lancer : php artisan config:cache'
        );

        // ── Cache routes ──
        $routeFiles   = glob(base_path('bootstrap/cache/routes-*.php')) ?: [];
        $routeCached  = !empty($routeFiles);
        $checks[]     = $this->check(
            'route_cache',
            'Cache routes',
            $routeCached ? 'Mis en cache' : 'Non mis en cache',
            $routeCached ? 'ok' : 'warning',
            $routeCached ? null : 'Lancer : php artisan route:cache'
        );

        // ── Cache vues ──
        $viewsDir     = storage_path('framework/views');
        $viewsCached  = is_dir($viewsDir) && count(scandir($viewsDir)) > 2;
        $checks[]     = $this->check(
            'view_cache',
            'Cache vues',
            $viewsCached ? 'Compilées' : 'Non compilées',
            $viewsCached ? 'ok' : 'warning',
            $viewsCached ? null : 'Lancer : php artisan view:cache'
        );

        // ── APP_DEBUG ──
        $debug    = (bool) config('app.debug');
        $checks[] = $this->check(
            'debug',
            'Mode Debug',
            $debug ? 'ACTIVÉ ⚠' : 'Désactivé',
            $debug ? 'warning' : 'ok',
            $debug ? 'Mettre APP_DEBUG=false dans .env' : null
        );

        // ── APP_ENV ──
        $env      = (string) config('app.env', 'production');
        $checks[] = $this->check(
            'app_env',
            'Environnement',
            $env,
            $env === 'production' ? 'ok' : 'warning',
            $env !== 'production' ? 'Mettre APP_ENV=production dans .env' : null
        );

        // ── Queue driver ──
        $queue    = (string) config('queue.default', 'sync');
        $checks[] = $this->check(
            'queue',
            'Driver Queue',
            $queue,
            $queue === 'sync' ? 'warning' : 'ok',
            $queue === 'sync' ? 'Mode sync : les notifications sont bloquantes (OK pour hébergement mutualisé)' : null
        );

        // ── Driver Mail ──
        $mail     = (string) config('mail.default', 'log');
        $mailOk   = in_array($mail, ['smtp', 'ses', 'mailgun', 'postmark', 'resend'], true);
        $checks[] = $this->check(
            'mail',
            'Driver Mail',
            $mail,
            $mailOk ? 'ok' : 'warning',
            $mailOk ? null : 'Configurer un vrai driver SMTP dans .env'
        );

        // ── Taille du fichier log ──
        $logFile   = storage_path('logs/laravel.log');
        $logSizeMb = file_exists($logFile) ? round(filesize($logFile) / 1048576, 1) : 0;
        $checks[]  = $this->check(
            'log_size',
            'Taille du log',
            "{$logSizeMb} MB",
            $logSizeMb > 50 ? 'critical' : ($logSizeMb > 10 ? 'warning' : 'ok'),
            $logSizeMb > 10 ? 'Archiver / vider storage/logs/laravel.log' : null
        );

        return $checks;
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    private function check(string $id, string $label, string $value, string $status, ?string $hint = null): array
    {
        return compact('id', 'label', 'value', 'status', 'hint');
    }

    private function getRecentErrors(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return [];
        }

        $errors = [];

        foreach ($this->tailFile($logFile, 300) as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY|WARNING): (.+)/', $line, $m)) {
                $errors[] = [
                    'time'    => $m[1],
                    'level'   => $m[2],
                    'message' => substr(trim($m[3]), 0, 160),
                ];
            }
        }

        return array_slice(array_reverse($errors), 0, 40);
    }

    /**
     * Lit les N dernières lignes d'un fichier sans tout charger en mémoire.
     *
     * @return string[]
     */
    private function tailFile(string $filepath, int $lines): array
    {
        if (! file_exists($filepath)) {
            return [];
        }

        $fp     = fopen($filepath, 'rb');
        $buffer = '';
        $chunk  = 4096;
        $count  = 0;

        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);

        while ($pos > 0 && $count <= $lines) {
            $step    = min($chunk, $pos);
            $pos    -= $step;
            fseek($fp, $pos);
            $buffer  = fread($fp, $step) . $buffer;
            $count   = substr_count($buffer, "\n");
        }

        fclose($fp);

        return array_slice(explode("\n", $buffer), -$lines);
    }

    private function getInstalledPackages(): array
    {
        $lockFile = base_path('composer.lock');

        if (! file_exists($lockFile)) {
            return [];
        }

        $lock = json_decode(file_get_contents($lockFile), true);

        return collect($lock['packages'] ?? [])
            ->map(fn ($p) => [
                'name'    => $p['name'],
                'version' => $p['version'],
                'desc'    => substr($p['description'] ?? '', 0, 80),
            ])
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    private function getLogSizeKb(): float
    {
        $logFile = storage_path('logs/laravel.log');

        return file_exists($logFile) ? round(filesize($logFile) / 1024, 1) : 0.0;
    }

    // ──────────────────────────────────────────────
    //  Alerting helpers
    // ──────────────────────────────────────────────

    /**
     * Détecte les erreurs dans le log qui n'ont pas encore été signalées.
     */
    private function detectNewErrors(): array
    {
        $lastCheckedPos = (int) Cache::get('health_last_log_position', 0);
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return [];
        }

        $currentSize = filesize($logFile);

        // Log was rotated or cleared
        if ($currentSize < $lastCheckedPos) {
            $lastCheckedPos = 0;
        }

        if ($currentSize <= $lastCheckedPos) {
            return [];
        }

        $fp = fopen($logFile, 'rb');
        fseek($fp, $lastCheckedPos);
        $newContent = fread($fp, min($currentSize - $lastCheckedPos, 524288)); // Max 512KB
        fclose($fp);

        Cache::put('health_last_log_position', $currentSize, 86400);

        $errors = [];
        $lines = explode("\n", $newContent);

        foreach ($lines as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.+)/', $line, $m)) {
                // Extract file and line if present
                $file = 'unknown';
                $lineNo = '0';
                if (preg_match('/in ([^\s:]+):(\d+)/', $m[3], $fm)) {
                    $file = basename($fm[1]);
                    $lineNo = $fm[2];
                }

                $errors[] = [
                    'time'    => $m[1],
                    'level'   => $m[2],
                    'message' => substr(trim($m[3]), 0, 200),
                    'file'    => $file,
                    'line'    => $lineNo,
                ];
            }
        }

        // Limit to 10 most recent to avoid notification spam
        return array_slice($errors, -10);
    }

    /**
     * Store alert history record.
     */
    private function storeAlertRecord(int $alertsSent, int $newErrors, int $criticalChecks): void
    {
        $history = Cache::get('system_alert_history', []);
        array_unshift($history, [
            'time'            => now()->toDateTimeString(),
            'alerts_sent'     => $alertsSent,
            'new_errors'      => $newErrors,
            'critical_checks' => $criticalChecks,
        ]);
        // Keep last 50 records
        Cache::put('system_alert_history', array_slice($history, 0, 50), 604800);
    }

    /**
     * Get alert history.
     */
    private function getAlertHistory(): array
    {
        return Cache::get('system_alert_history', []);
    }
}
