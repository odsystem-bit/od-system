<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-approuve les commandes UGC livrees depuis plus de 48h
Schedule::command('mantota:auto-approve-ugc')->hourly();

// Anti-fraude : relance les campagnes en pause depuis plus d'1h
Schedule::command('mantota:unpause-campaigns')->everyMinute();

// Expire les abonnements ambassadeur dont la date est depassee
Schedule::command('mantota:expire-ambassador-subscriptions')->daily();
