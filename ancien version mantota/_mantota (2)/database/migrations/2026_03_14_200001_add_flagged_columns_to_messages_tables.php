<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_dispute_messages', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('message');
            $table->text('original_message')->nullable()->after('is_flagged');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('body');
            $table->text('original_message')->nullable()->after('is_flagged');
        });
    }

    public function down(): void
    {
        Schema::table('order_dispute_messages', function (Blueprint $table) {
            $table->dropColumn(['is_flagged', 'original_message']);
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropColumn(['is_flagged', 'original_message']);
        });
    }
};
