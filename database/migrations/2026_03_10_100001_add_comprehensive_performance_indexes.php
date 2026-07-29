<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajout comprehensif d'index de performance pour supporter 10,000 utilisateurs + 200 connexions.
 *
 * Strategie :
 *  - Queries frecentes : WHERE + JOIN sur ces colonnes = indexer les colonnes de filtrage
 *  - Ordering : created_at, updated_at = inclure dans index pour eviter sort en DB
 *  - Foreign keys : Implicites mais explicites pour lockForUpdate() et batch operations
 *
 * Tables optimisees :
 *  - service_orders : requetes par status/vendor/influencer (dashboard moderation)
 *  - smart_links : requetes par hash (tracking) et influencer_id (performance)
 *  - click_logs : requetes par smart_link_id, is_valid (analytics)
 *  - transactions ou withdrawals : requetes par user_id, status (wallet history)
 *  - orders : requetes par user_id, status (order tracking)
 *  - wallets : couvert par FK mais explicit pour performance de lock
 */
return new class extends Migration
{
    /**
     * Helper : cree un index seulement s'il n'existe pas deja.
     */
    private function addIndexIfMissing(string $table, array|string $columns, string $indexName): void
    {
        $exists = \Illuminate\Support\Facades\DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        if (empty($exists)) {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        }
    }

    public function up(): void
    {
        // ===== SERVICE ORDERS =====
        $this->addIndexIfMissing('service_orders', ['status', 'created_at'], 'idx_service_orders_status_created');
        $this->addIndexIfMissing('service_orders', ['vendor_id', 'status'], 'idx_service_orders_vendor_status');
        $this->addIndexIfMissing('service_orders', ['influencer_id', 'status'], 'idx_service_orders_influencer_status');
        $this->addIndexIfMissing('service_orders', ['service_id', 'created_at'], 'idx_service_orders_service_created');

        // ===== SMART LINKS =====
        $this->addIndexIfMissing('smart_links', ['unique_hash'], 'idx_smart_links_hash');
        $this->addIndexIfMissing('smart_links', ['influencer_id', 'created_at'], 'idx_smart_links_influencer_created');
        $this->addIndexIfMissing('smart_links', ['campaign_id', 'created_at'], 'idx_smart_links_campaign_created');
        // smart_links n'a pas de colonne 'status' — index ignore

        // ===== CLICK LOGS =====
        $this->addIndexIfMissing('click_logs', ['smart_link_id', 'is_valid'], 'idx_click_logs_link_valid');
        $this->addIndexIfMissing('click_logs', ['ip_address', 'created_at'], 'idx_click_logs_ip_created');
        $this->addIndexIfMissing('click_logs', ['created_at', 'smart_link_id'], 'idx_click_logs_created_link');

        // ===== TRANSACTIONS =====
        $this->addIndexIfMissing('transactions', ['user_id', 'status', 'created_at'], 'idx_transactions_user_status_created');
        $this->addIndexIfMissing('transactions', ['status', 'type', 'created_at'], 'idx_transactions_status_type_created');

        // ===== ORDERS =====
        $this->addIndexIfMissing('orders', ['vendor_id', 'status'], 'idx_orders_user_status');
        $this->addIndexIfMissing('orders', ['vendor_id', 'created_at'], 'idx_orders_user_created');
        $this->addIndexIfMissing('orders', ['influencer_id', 'status'], 'idx_orders_influencer_status');
        $this->addIndexIfMissing('orders', ['status', 'dispute_reason'], 'idx_orders_status_dispute');

        // ===== WALLETS =====
        $this->addIndexIfMissing('wallets', ['user_id'], 'idx_wallets_user');

        // ===== CAMPAIGNS =====
        $this->addIndexIfMissing('campaigns', ['status', 'created_at'], 'idx_campaigns_status_created');
        $this->addIndexIfMissing('campaigns', ['vendor_id', 'status'], 'idx_campaigns_vendor_status');

        // ===== NOTIFICATIONS =====
        if (Schema::hasTable('notifications')) {
            $this->addIndexIfMissing('notifications', ['notifiable_id', 'notifiable_type', 'read_at'], 'idx_notifications_user_unread');
            $this->addIndexIfMissing('notifications', ['created_at', 'notifiable_id'], 'idx_notifications_created');
        }
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropIndex('idx_service_orders_status_created');
            $table->dropIndex('idx_service_orders_vendor_status');
            $table->dropIndex('idx_service_orders_influencer_status');
            $table->dropIndex('idx_service_orders_service_created');
        });

        Schema::table('smart_links', function (Blueprint $table) {
            $table->dropIndex('idx_smart_links_hash');
            $table->dropIndex('idx_smart_links_influencer_created');
            $table->dropIndex('idx_smart_links_campaign_created');
        });

        Schema::table('click_logs', function (Blueprint $table) {
            $table->dropIndex('idx_click_logs_link_valid');
            $table->dropIndex('idx_click_logs_ip_created');
            $table->dropIndex('idx_click_logs_created_link');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_user_status_created');
            $table->dropIndex('idx_transactions_status_type_created');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_user_status');
            $table->dropIndex('idx_orders_user_created');
            $table->dropIndex('idx_orders_influencer_status');
            $table->dropIndex('idx_orders_status_dispute');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropIndex('idx_wallets_user');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('idx_campaigns_status_created');
            $table->dropIndex('idx_campaigns_vendor_status');
        });

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notifications_user_unread');
                $table->dropIndex('idx_notifications_created');
            });
        }
    }
};
