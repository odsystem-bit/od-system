<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Console\Command;

/**
 * Relance automatiquement les campagnes en pause depuis plus d'1 heure.
 *
 * Protection anti-fraude : empeche les vendeurs de mettre en pause
 * indefiniment pour recevoir du trafic gratuit des créateurs de contenu.
 */
class UnpauseCampaigns extends Command
{
    protected $signature = 'mantota:unpause-campaigns';

    protected $description = 'Relance les campagnes en pause depuis plus d\'1 heure';

    public function handle(): int
    {
        $count = Campaign::query()
            ->where('status', CampaignStatus::PAUSED)
            ->whereNotNull('paused_at')
            ->where('paused_at', '<=', now()->subHour())
            ->update([
                'status'    => CampaignStatus::ACTIVE,
                'paused_at' => null,
            ]);

        if ($count > 0) {
            $this->info("{$count} campagne(s) relancee(s) automatiquement.");
        }

        return self::SUCCESS;
    }
}
