<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes tier et open_sea a la table campaigns.
 *
 * tier     : palier de visibilite (bronze / argent / or).
 * open_sea : supplément pour ouvrir la campagne a tous les paliers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('tier', 10)->default('bronze')->after('status');
            $table->boolean('open_sea')->default(false)->after('tier');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['tier', 'open_sea']);
        });
    }
};
