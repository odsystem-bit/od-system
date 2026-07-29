<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `campaigns` MODIFY COLUMN `status` ENUM('draft','active','paused','completed','deleted','expired','rejected') NOT NULL DEFAULT 'draft'");

        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        DB::statement("ALTER TABLE `campaigns` MODIFY COLUMN `status` ENUM('draft','active','paused','completed','deleted','expired') NOT NULL DEFAULT 'draft'");
    }
};
