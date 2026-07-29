<?php

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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('participation_mode', 20)->default('open')->after('restricted_circle');
            $table->unsignedInteger('max_participants')->nullable()->after('participation_mode');
            $table->unsignedInteger('current_participants')->default(0)->after('max_participants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['participation_mode', 'max_participants', 'current_participants']);
        });
    }
};
