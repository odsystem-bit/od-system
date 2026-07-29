<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Convertir les valeurs string existantes en tableaux JSON
        $campaigns = DB::table('campaigns')
            ->whereNotNull('target_country')
            ->where('target_country', '!=', '')
            ->get(['id', 'target_country']);

        foreach ($campaigns as $campaign) {
            // Si ce n'est pas deja du JSON valide (tableau), convertir
            $decoded = json_decode($campaign->target_country, true);
            if (! is_array($decoded)) {
                DB::table('campaigns')
                    ->where('id', $campaign->id)
                    ->update(['target_country' => json_encode([$campaign->target_country])]);
            }
        }

        // 2. Changer le type de colonne en JSON
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('target_country')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('target_country')->nullable()->change();
        });

        // Re-convertir les tableaux JSON en simple string (premier element)
        $campaigns = DB::table('campaigns')
            ->whereNotNull('target_country')
            ->get(['id', 'target_country']);

        foreach ($campaigns as $campaign) {
            $decoded = json_decode($campaign->target_country, true);
            if (is_array($decoded)) {
                DB::table('campaigns')
                    ->where('id', $campaign->id)
                    ->update(['target_country' => $decoded[0] ?? null]);
            }
        }
    }
};
