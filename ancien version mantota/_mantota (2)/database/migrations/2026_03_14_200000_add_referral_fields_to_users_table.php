<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 10)->nullable()->unique()->after('email');
            $table->unsignedBigInteger('referred_by')->nullable()->after('referral_code');
            $table->unsignedInteger('referral_count')->default(0)->after('referred_by');
            $table->decimal('referral_earnings', 12, 2)->default(0)->after('referral_count');

            $table->foreign('referred_by')->references('id')->on('users')->nullOnDelete();
            $table->index('referral_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by', 'referral_count', 'referral_earnings']);
        });
    }
};
