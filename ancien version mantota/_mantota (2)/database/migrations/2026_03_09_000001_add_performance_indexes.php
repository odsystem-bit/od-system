<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajout d'index de performance critiques pour le pre-lancement.
 *
 * Tables ciblees :
 *  - campaigns : index sur (status, remaining_budget) pour le dashboard créateur de contenu.
 *  - transactions : index sur (type, status) pour les KPI admin et moderation.
 *  - wallets : index sur user_id (couvert par FK, mais explicite pour lockForUpdate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['status', 'remaining_budget'], 'idx_campaigns_status_budget');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['type', 'status'], 'idx_transactions_type_status');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('idx_campaigns_status_budget');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_type_status');
        });
    }
};
