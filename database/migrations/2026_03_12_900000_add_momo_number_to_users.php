<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('momo_number', 30)->nullable()->after('phone');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('recipient_phone', 30)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('momo_number');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('recipient_phone');
        });
    }
};