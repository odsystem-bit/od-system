<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Force HTTPS en production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Politique de mot de passe renforcee
        Password::defaults(function () {
            return $this->app->environment('production')
                ? Password::min(8)->letters()->mixedCase()->numbers()
                : Password::min(8);
        });

        // Gate UGC : seuls les créateurs de contenu VIP peuvent acceder aux fonctions UGC.
        Gate::define('access-ugc', function (User $user): bool {
            return $user->role === UserRole::INFLUENCER && $user->is_vip === true;
        });

        // ===== QUEUE CONFIGURATION FOR PHASE 2 SCALABILITY =====
        // Monitoring : log les jobs traites et echoues
        Queue::after(function (JobProcessed $event) {
            Log::channel('queue')->info('Job processed', [
                'job' => $event->job->resolveName(),
            ]);
        });

        Queue::failing(function (JobFailed $event) {
            Log::channel('queue')->error('Job failed', [
                'job' => $event->job->resolveName(),
                'exception' => $event->exception->getMessage(),
                'attempts' => $event->job->attempts(),
            ]);
        });
    }
}
