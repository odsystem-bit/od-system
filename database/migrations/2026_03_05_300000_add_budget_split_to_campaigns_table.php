<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->decimal('budget_clicks', 10, 2)->default(0)->after('total_budget');
            $table->decimal('budget_views', 10, 2)->default(0)->after('budget_clicks');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn(['budget_clicks', 'budget_views']);
        });
    }
};
