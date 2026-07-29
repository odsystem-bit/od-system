<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('digital_delivery_type', 20)->nullable()->after('access_url'); // 'link' | 'file'
            $table->string('digital_file_path')->nullable()->after('digital_delivery_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['digital_delivery_type', 'digital_file_path']);
        });
    }
};
