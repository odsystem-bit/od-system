<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'idx_users_role');
            $table->index('kyc_status', 'idx_users_kyc_status');
            $table->index('deleted_at', 'idx_users_deleted_at');
            $table->index('is_vip', 'idx_users_is_vip');
            $table->index('is_banned', 'idx_users_is_banned');
            $table->index('is_ambassador', 'idx_users_is_ambassador');
            $table->index('country', 'idx_users_country');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('customer_phone', 'idx_orders_customer_phone');
            $table->index('campaign_id', 'idx_orders_campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_kyc_status');
            $table->dropIndex('idx_users_deleted_at');
            $table->dropIndex('idx_users_is_vip');
            $table->dropIndex('idx_users_is_banned');
            $table->dropIndex('idx_users_is_ambassador');
            $table->dropIndex('idx_users_country');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_customer_phone');
            $table->dropIndex('idx_orders_campaign_id');
        });
    }
};
