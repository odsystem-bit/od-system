<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table orders — Commandes e-commerce avec sequestre (Escrow) MANTOTA.
 *
 * Le montant est bloque en escrow_balance dans les wallets du vendeur
 * et du créateur de contenu. Il n'est libere que lorsque le statut passe a 'delivered'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            // Reference courte unique (ex: CMD-A3F7)
            $table->string('reference', 20)->unique();

            // Relations
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('influencer_id')->nullable()->constrained('users')->nullOnDelete();

            // Informations client (Guest Checkout — pas de compte)
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_whatsapp', 30);

            // Localisation livraison (realites africaines)
            $table->string('city');
            $table->text('landmark_indication'); // Quartier & Repere precis

            // Repartition financiere
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->decimal('vendor_earnings', 10, 2);

            // Statut et deadline
            $table->string('status')->default('pending');
            $table->timestamp('delivery_deadline')->nullable();

            $table->timestamps();

            // Index pour le dashboard vendeur
            $table->index(['vendor_id', 'status']);
            $table->index(['influencer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
