<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute un index composite pour accelerer la verification de deduplication
 * IP par campagne et prevenir les race conditions au niveau BDD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            // Index performant pour la requete de deduplication :
            // WHERE smart_link_id IN (...) AND ip_address = ? AND is_valid = true
            $table->index(['ip_address', 'is_valid', 'smart_link_id'], 'click_logs_dedup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->dropIndex('click_logs_dedup_idx');
        });
    }
};
