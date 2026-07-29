<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la politique de livraison aux produits physiques.
 *
 * delivery_type : 'free' | 'fixed' | 'pay_on_delivery'
 * delivery_fee  : montant fixe (nullable, utilise uniquement si delivery_type = 'fixed')
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('delivery_type')->nullable()->after('stock');
            $table->decimal('delivery_fee', 10, 2)->nullable()->after('delivery_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['delivery_type', 'delivery_fee']);
        });
    }
};
