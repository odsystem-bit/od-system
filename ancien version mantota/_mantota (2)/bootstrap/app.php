<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\BlockedIpMiddleware::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\CheckBanned::class,
            \App\Http\Middleware\TrackPageVisit::class,
        ]);

        // Alias 'role' pour le middleware de filtrage par role utilisateur.
        // Alias 'admin.ip' pour la restriction IP de l'interface admin.
        $middleware->alias([
            'role'             => \App\Http\Middleware\EnsureUserRole::class,
            'admin.ip'         => \App\Http\Middleware\AdminIpWhitelist::class,
            'admin.permission' => \App\Http\Middleware\CheckAdminPermission::class,
        ]);

        // Exclure le webhook FedaPay de la vérification CSRF.
        // FedaPay signe ses requêtes via HMAC-SHA256 (header X-Fedapay-Signature),
        // vérifié dans FedaPayController@webhook — le token CSRF est donc inutile.
        $middleware->validateCsrfTokens(except: [
            'webhook/fedapay',
            'webhooks/fedapay',
            'webhook/paydunya',
            'webhooks/paydunya',
            'webhooks/cinetpay',
            'webhooks/flutterwave',
            'webhooks/feexpay',
            'go/*/click',
            'track/time',
        ]);

        // ── Redirections auth/guest isolees admin vs user ──

        // Middleware « guest » : utilisateur deja connecte → rediriger
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('admin/*') || $request->is('admin')) {
                return route('admin.dashboard');
            }
            if ($request->is('vendor/*') || $request->is('vendor')) {
                return route('vendor.dashboard');
            }
            if ($request->is('influencer/*') || $request->is('influencer')) {
                return route('influencer.dashboard');
            }
            return '/dashboard';
        });

        // Middleware « auth » : utilisateur non connecte → rediriger vers login
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin/*') || $request->is('admin')) {
                return route('admin.login');
            }
            if ($request->is('vendor/*') || $request->is('vendor')) {
                return route('vendor.login');
            }
            if ($request->is('influencer/*') || $request->is('influencer')) {
                return route('influencer.login');
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ── Alert admins on critical errors ──
        $exceptions->reportable(function (\Throwable $e) {
            try {
                if (app()->environment('production') && \Illuminate\Support\Facades\Cache::get('system_alerts_enabled', true)) {
                    $cacheKey = 'error_alert_' . md5($e->getMessage() . $e->getFile());
                    if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 300); // 5 min throttle per unique error
                        $admins = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->get();
                        foreach ($admins as $admin) {
                            $admin->notify(new \App\Notifications\SystemErrorNotification(
                                'ERROR',
                                mb_substr($e->getMessage(), 0, 200),
                                basename($e->getFile()),
                                (string) $e->getLine()
                            ));
                        }
                    }
                }
            } catch (\Throwable) {
                // Silently fail — Cache/DB may not be available during early boot errors
            }
        })->stop(false);

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, Request $request) {
            if (! app()->environment(['local', 'testing'])
                && in_array($response->getStatusCode(), [500, 503, 404, 403, 422])) {
                return \Inertia\Inertia::render('Error', [
                    'status' => $response->getStatusCode(),
                ])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
            }

            if ($response->getStatusCode() === 419) {
                return back()->with('error', 'La page a expire, veuillez reessayer.');
            }

            return $response;
        });
    })->create();
