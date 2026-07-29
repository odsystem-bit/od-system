<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table smart_links — Liens de tracking uniques avec durée de vie de 48 h.
 *
 * Chaque smart_link lie un créateur de contenu à une campagne via :
 *  • unique_hash → identifiant court et unique du lien partagé.
 *  • proof_url   → URL de la publication (vidéo/post) fournie par le créateur de contenu.
 *  • expires_at  → horodatage d'expiration automatique (NOW + 48 h).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                  ->constrained('campaigns')
                  ->cascadeOnDelete();

            $table->foreignId('influencer_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('unique_hash')->unique()->index();
            $table->string('proof_url')->nullable();

            $table->timestamp('expires_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_links');
    }
};
