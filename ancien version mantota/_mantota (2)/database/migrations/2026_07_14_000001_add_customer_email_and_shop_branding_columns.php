<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute :
 *  - customer_email sur orders  (produits digitaux — reception par email)
 *  - shop_name + shop_logo_path sur users (branding boutique vendeur)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->after('customer_whatsapp');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('shop_name')->nullable()->after('business_name');
            $table->string('shop_logo_path')->nullable()->after('shop_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shop_name', 'shop_logo_path']);
        });
    }
};
