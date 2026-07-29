<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moteur Ads MANTOTA — Colonnes supplementaires sur la table campaigns.
 *
 *  - product_id       : Lie la campagne a un produit de la Boutique (Affiliation / CPC).
 *  - click_price      : Prix par clic (CPC) fixe par le vendeur (min 15 FCFA).
 *  - remaining_budget : Budget restant apres consommation des clics/vues.
 *
 * Le total_budget est debite du wallet au lancement ;
 * remaining_budget decremente a chaque clic comptabilise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('product_id')
                  ->nullable()
                  ->after('vendor_id')
                  ->constrained('products')
                  ->nullOnDelete();

            $table->decimal('click_price', 8, 2)
                  ->nullable()
                  ->after('total_budget');

            $table->decimal('remaining_budget', 10, 2)
                  ->default(0)
                  ->after('click_price');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'click_price', 'remaining_budget']);
        });
    }
};
