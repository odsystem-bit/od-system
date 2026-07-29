<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway', 20)->nullable()->after('payment_gateway_ref')
                ->comment('Slug de la passerelle utilisee (fedapay, paydunya)');
        });

        // Backfill : les commandes existantes ayant un gateway_ref numérique sont FedaPay
        DB::table('orders')
            ->whereNotNull('payment_gateway_ref')
            ->whereNull('payment_gateway')
            ->update(['payment_gateway' => 'fedapay']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_gateway');
        });
    }
};
