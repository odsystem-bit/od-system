<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('bot_status', ['inactive', 'active'])->default('inactive');
            $table->enum('bot_access_type', ['none', 'manual', 'paid'])->default('none');
            $table->string('whatsapp_bot_number', 30)->nullable();
            $table->enum('shop_type', ['physical', 'digital', 'both'])->nullable();
            $table->text('shop_address')->nullable();
            $table->decimal('shop_latitude', 10, 7)->nullable();
            $table->decimal('shop_longitude', 10, 7)->nullable();
            $table->string('bot_plan', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bot_status',
                'bot_access_type',
                'whatsapp_bot_number',
                'shop_type',
                'shop_address',
                'shop_latitude',
                'shop_longitude',
                'bot_plan',
            ]);
        });
    }
};
