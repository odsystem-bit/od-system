<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Robustness audit — Session 5 :
 *  1. Composite indexes for admin listings (role+created_at, kyc_status+created_at, is_vip+created_at).
 *  2. Index on products.vendor_id for ShopController queries.
 *  3. Foreign key constraints on orders, campaigns, smart_links.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Composite indexes on users ──
        $this->addIndexIfMissing('users', ['role', 'created_at'], 'idx_users_role_created');
        $this->addIndexIfMissing('users', ['kyc_status', 'created_at'], 'idx_users_kyc_created');
        $this->addIndexIfMissing('users', ['is_vip', 'created_at'], 'idx_users_vip_created');

        // ── Index on products.vendor_id ──
        $this->addIndexIfMissing('products', ['vendor_id'], 'idx_products_vendor_id');

        // ── Foreign key constraints ──
        $this->addFkIfMissing('orders', 'vendor_id', 'users', 'id', 'fk_orders_vendor');
        $this->addFkIfMissing('orders', 'product_id', 'products', 'id', 'fk_orders_product');
        $this->addFkIfMissing('orders', 'influencer_id', 'users', 'id', 'fk_orders_influencer');
        $this->addFkIfMissing('orders', 'campaign_id', 'campaigns', 'id', 'fk_orders_campaign');
        $this->addFkIfMissing('campaigns', 'vendor_id', 'users', 'id', 'fk_campaigns_vendor');
        $this->addFkIfMissing('campaigns', 'product_id', 'products', 'id', 'fk_campaigns_product');
        $this->addFkIfMissing('smart_links', 'campaign_id', 'campaigns', 'id', 'fk_smart_links_campaign');
        $this->addFkIfMissing('smart_links', 'influencer_id', 'users', 'id', 'fk_smart_links_influencer');
    }

    public function down(): void
    {
        // Drop FKs
        $fks = [
            'orders'      => ['fk_orders_vendor', 'fk_orders_product', 'fk_orders_influencer', 'fk_orders_campaign'],
            'campaigns'   => ['fk_campaigns_vendor', 'fk_campaigns_product'],
            'smart_links' => ['fk_smart_links_campaign', 'fk_smart_links_influencer'],
        ];

        foreach ($fks as $table => $keys) {
            Schema::table($table, function (Blueprint $t) use ($keys) {
                foreach ($keys as $key) {
                    try { $t->dropForeign($key); } catch (\Exception) { }
                }
            });
        }

        // Drop indexes
        $indexes = [
            'users'    => ['idx_users_role_created', 'idx_users_kyc_created', 'idx_users_vip_created'],
            'products' => ['idx_products_vendor_id'],
        ];

        foreach ($indexes as $table => $idxList) {
            Schema::table($table, function (Blueprint $t) use ($idxList) {
                foreach ($idxList as $idx) {
                    try { $t->dropIndex($idx); } catch (\Exception) { }
                }
            });
        }
    }

    // ── Helpers ──

    private function addIndexIfMissing(string $table, array $columns, string $name): void
    {
        if (! $this->hasIndex($table, $name)) {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        $rows = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]);
        return count($rows) > 0;
    }

    private function addFkIfMissing(string $table, string $column, string $refTable, string $refColumn, string $name): void
    {
        // Check if FK already exists
        $fks = \Illuminate\Support\Facades\DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = ?",
            [$table, $name]
        );

        if (count($fks) === 0) {
            // Check if column is nullable — use SET NULL for nullable, RESTRICT for NOT NULL
            $colInfo = \Illuminate\Support\Facades\DB::select(
                "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$table, $column]
            );
            $nullable = isset($colInfo[0]) && $colInfo[0]->IS_NULLABLE === 'YES';

            Schema::table($table, function (Blueprint $t) use ($column, $refTable, $refColumn, $name, $nullable) {
                $fk = $t->foreign($column, $name)->references($refColumn)->on($refTable);
                $nullable ? $fk->nullOnDelete() : $fk->restrictOnDelete();
            });
        }
    }
};
