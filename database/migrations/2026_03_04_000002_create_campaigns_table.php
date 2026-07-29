<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table campaigns — Campagnes publicitaires créées par les vendors.
 *
 * Chaque campagne contient :
 *  • Les métadonnées (titre, description, URL cible).
 *  • Le budget total alloué.
 *  • Un statut de cycle de vie (draft → active → paused → completed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('target_url');

            $table->decimal('total_budget', 12, 2);

            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
