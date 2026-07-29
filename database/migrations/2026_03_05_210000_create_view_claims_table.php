<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration — Table view_claims.
 *
 * Systeme de reclamation des vues (CPM) par les créateurs de contenu.
 * Chaque reclamation reference une campagne, le créateur de contenu, l'URL publique
 * de la video et le nombre de vues reclamees/approuvees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('view_claims', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('influencer_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('video_url');
            $table->unsignedInteger('claimed_views');
            $table->unsignedInteger('approved_views')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');

            $table->decimal('amount_paid', 10, 2)->nullable();

            $table->timestamps();

            // Un créateur de contenu ne peut pas soumettre plusieurs reclamations
            // pour la meme campagne en statut pending simultanement
            $table->index(['campaign_id', 'influencer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('view_claims');
    }
};
