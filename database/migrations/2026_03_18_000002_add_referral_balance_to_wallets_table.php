<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute un solde parrainage séparé au wallet.
 *
 * Les bonus de parrainage sont crédités ici (et non dans balance).
 * L'utilisateur peut transférer vers balance une fois le seuil atteint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('referral_balance', 12, 2)->default(0.00)->after('pending_balance');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('referral_balance');
        });
    }
};
