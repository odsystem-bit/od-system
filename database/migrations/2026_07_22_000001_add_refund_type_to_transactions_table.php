<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN `type` ENUM('deposit','withdrawal','earning','fee','referral_bonus','refund') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN `type` ENUM('deposit','withdrawal','earning','fee','referral_bonus') NOT NULL");
    }
};
