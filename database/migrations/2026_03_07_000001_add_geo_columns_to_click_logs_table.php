<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes de geo-fencing anti-fraude a la table click_logs.
 *
 * - clicker_country : code pays ISO 3166-1 alpha-2 du visiteur (ex: 'BJ', 'FR')
 * - is_valid        : le clic est-il geographiquement valide ?
 * - invalid_reason  : raison d'invalidation (ex: 'geo_mismatch')
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->string('clicker_country', 5)->nullable()->after('ip_address');
            $table->boolean('is_valid')->default(true)->after('is_paid');
            $table->string('invalid_reason')->nullable()->after('is_valid');
        });
    }

    public function down(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->dropColumn(['clicker_country', 'is_valid', 'invalid_reason']);
        });
    }
};
