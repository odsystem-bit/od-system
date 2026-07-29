<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ambassador_subscribed_at')->nullable()->after('ambassador_tier');
            $table->timestamp('ambassador_expires_at')->nullable()->after('ambassador_subscribed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ambassador_subscribed_at', 'ambassador_expires_at']);
        });
    }
};
