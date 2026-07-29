<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suppression totale du systeme de paiement par vues (CPM).
 *
 * MANTOTA devient 100% Performance :
 *  - Cout par Clic (CPC)
 *  - Commission sur Vente (Affiliation)
 *
 * Colonnes supprimees de `campaigns` :
 *  - budget_clicks (plus de split)
 *  - budget_views  (plus de split)
 *
 * Table supprimee :
 *  - view_claims
 */
return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les colonnes budget_clicks et budget_views
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['budget_clicks', 'budget_views']);
        });

        // Supprimer la table view_claims
        Schema::dropIfExists('view_claims');
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->decimal('budget_clicks', 10, 2)->default(0)->after('total_budget');
            $table->decimal('budget_views', 10, 2)->default(0)->after('budget_clicks');
        });

        Schema::create('view_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('influencer_id')->constrained('users')->cascadeOnDelete();
            $table->string('video_url', 1000);
            $table->unsignedInteger('claimed_views');
            $table->unsignedInteger('approved_views')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->timestamps();
        });
    }
};
