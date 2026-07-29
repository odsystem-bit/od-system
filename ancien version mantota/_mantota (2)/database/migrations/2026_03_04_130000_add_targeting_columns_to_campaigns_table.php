<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes de ciblage avance a la table campaigns.
 *
 *  - social_network    : reseau social principal (tiktok, instagram, facebook, linkedin).
 *  - influencer_type   : type de créateur de contenu recherche (nano, micro, macro, mega).
 *  - target_countries  : pays cibles (JSON array).
 *
 * Ces colonnes permettent au vendeur de preciser le ciblage
 * lors de la creation d'une campagne via le wizard multi-etapes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('social_network', ['tiktok', 'instagram', 'facebook', 'linkedin'])
                  ->nullable()
                  ->after('media_type');

            $table->enum('influencer_type', ['nano', 'micro', 'macro', 'mega'])
                  ->nullable()
                  ->after('social_network');

            $table->json('target_countries')
                  ->nullable()
                  ->after('influencer_type');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['social_network', 'influencer_type', 'target_countries']);
        });
    }
};
