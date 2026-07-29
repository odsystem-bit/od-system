<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les informations du livreur assigne par le vendeur.
 *
 * delivery_guy_name  : Nom du livreur
 * delivery_guy_phone : Telephone du livreur
 *
 * Ces champs sont remplis quand le vendeur passe la commande en 'shipped'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_guy_name')->nullable()->after('landmark_indication');
            $table->string('delivery_guy_phone', 30)->nullable()->after('delivery_guy_name');
            $table->decimal('delivery_fee_paid', 10, 2)->default(0.00)->after('vendor_earnings');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['delivery_guy_name', 'delivery_guy_phone', 'delivery_fee_paid']);
        });
    }
};
