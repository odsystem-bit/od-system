<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('vendor_defense_message')->nullable()->after('dispute_reason');
            $table->string('vendor_defense_proof')->nullable()->after('vendor_defense_message');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vendor_defense_message', 'vendor_defense_proof']);
        });
    }
};
