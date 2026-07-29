<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Desactive les abonnements ambassadeur expires.
 *
 * Planifie via Schedule::command('mantota:expire-ambassador-subscriptions')->daily();
 */
class ExpireAmbassadorSubscriptions extends Command
{
    protected $signature = 'mantota:expire-ambassador-subscriptions';

    protected $description = 'Desactive les abonnements ambassadeur dont la date d\'expiration est depassee';

    public function handle(): int
    {
        $count = User::query()
            ->where('is_ambassador', true)
            ->whereNotNull('ambassador_expires_at')
            ->where('ambassador_expires_at', '<=', now())
            ->where(function ($q) {
                $q->where('ambassador_source', '!=', 'purchased')
                  ->orWhereNull('ambassador_source');
            })
            ->update([
                'is_ambassador'     => false,
                'ambassador_tier'   => null,
            ]);

        if ($count > 0) {
            $this->info("{$count} abonnement(s) ambassadeur expire(s) et desactive(s).");
        }

        return self::SUCCESS;
    }
}
