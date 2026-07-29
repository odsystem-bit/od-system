<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute aux orders :
 *  - payment_status      : 'awaiting' (en attente paiement) | 'paid' (confirmé) | 'refunded' (remboursé)
 *  - payment_gateway_ref : référence de la transaction côté passerelle
 *  - cancel_reason       : raison d'annulation par le vendeur
 *
 * Anciens orders (payment simulé) reçoivent payment_status = 'paid' par défaut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_status', 20)->default('paid')->after('status');
            $table->string('payment_gateway_ref')->nullable()->after('payment_status');
            $table->text('cancel_reason')->nullable()->after('vendor_defense_proof');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['payment_status', 'payment_gateway_ref', 'cancel_reason']);
        });
    }
};
