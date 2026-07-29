<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table transactions — Journal financier complet avec décomposition des marges.
 *
 * Chaque transaction enregistre :
 *  • amount_target   → montant net (crédité au wallet ou au budget).
 *  • gateway_fee     → frais réels prélevés par FedaPay.
 *  • mantota_markup  → marge additionnelle de la plateforme MANTOTA.
 *  • amount_total    → montant brut réellement payé / reçu par l'utilisateur.
 *
 * Formule :  amount_total = amount_target + gateway_fee + mantota_markup
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->enum('type', ['deposit', 'withdrawal', 'earning', 'fee']);

            $table->decimal('amount_target', 12, 2);
            $table->decimal('gateway_fee', 12, 2)->default(0.00);
            $table->decimal('mantota_markup', 12, 2)->default(0.00);
            $table->decimal('amount_total', 12, 2);

            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');

            $table->string('reference')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
