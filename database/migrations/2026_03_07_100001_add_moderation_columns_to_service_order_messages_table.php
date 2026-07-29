<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_order_messages', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('attachment_path');
            $table->text('original_message')->nullable()->after('is_flagged');
        });
    }

    public function down(): void
    {
        Schema::table('service_order_messages', function (Blueprint $table) {
            $table->dropColumn(['is_flagged', 'original_message']);
        });
    }
};
