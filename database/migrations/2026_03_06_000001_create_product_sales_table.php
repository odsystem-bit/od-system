<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table product_sales — Journal des ventes e-commerce du mini-site.
 *
 * Chaque vente enregistre la repartition financiere :
 *  amount_paid       = montant total paye par le client.
 *  commission_amount = part reversee au créateur de contenu affilie (si present).
 *  vendor_earnings   = part reversee au vendeur (amount_paid - commission_amount).
 *
 * Formule :  amount_paid = commission_amount + vendor_earnings
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sales', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('vendor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('influencer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->decimal('amount_paid', 10, 2);
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->decimal('vendor_earnings', 10, 2);

            $table->enum('status', ['completed', 'refunded'])->default('completed');

            $table->string('reference')->unique();

            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['influencer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
