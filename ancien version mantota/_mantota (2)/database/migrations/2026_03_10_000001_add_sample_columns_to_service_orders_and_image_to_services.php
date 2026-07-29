<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── service_orders : colonnes de suivi d'echantillon ──
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('sample_status', 30)->default('not_required')->after('status');
            $table->text('sample_tracking_info')->nullable()->after('sample_status');
            $table->timestamp('production_started_at')->nullable()->after('delivered_at');
        });

        // ── influencer_services : image du service ──
        Schema::table('influencer_services', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['sample_status', 'sample_tracking_info', 'production_started_at']);
        });

        Schema::table('influencer_services', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
