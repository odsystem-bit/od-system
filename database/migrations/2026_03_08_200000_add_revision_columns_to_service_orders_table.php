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
            $table->unsignedTinyInteger('revisions_allowed')->default(1)->after('video_path');
            $table->unsignedTinyInteger('revisions_used')->default(0)->after('revisions_allowed');
            $table->text('revision_feedback')->nullable()->after('revisions_used');
            $table->timestamp('delivered_at')->nullable()->after('revision_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['revisions_allowed', 'revisions_used', 'revision_feedback', 'delivered_at']);
        });
    }
};
