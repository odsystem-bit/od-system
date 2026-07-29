<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les champs UGC et Social Commerce a la table campaigns :
 * demande de creation video, commission sur vente, ciblage pays, plateformes, logistique produit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->boolean('is_ugc')->default(false)->after('status');
            $table->decimal('ugc_price', 10, 2)->nullable()->after('is_ugc');
            $table->decimal('commission_percent', 5, 2)->nullable()->after('ugc_price');
            $table->string('target_country')->nullable()->after('commission_percent');
            $table->json('platforms')->nullable()->after('target_country');
            $table->string('product_handling')->nullable()->after('platforms');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn([
                'is_ugc',
                'ugc_price',
                'commission_percent',
                'target_country',
                'platforms',
                'product_handling',
            ]);
        });
    }
};
