<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('sample_tracking_info');
            $table->string('sample_delivery_guy_name', 255)->nullable()->after('sample_status');
            $table->string('sample_delivery_guy_phone', 50)->nullable()->after('sample_delivery_guy_name');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['sample_delivery_guy_name', 'sample_delivery_guy_phone']);
            $table->text('sample_tracking_info')->nullable()->after('sample_status');
        });
    }
};
