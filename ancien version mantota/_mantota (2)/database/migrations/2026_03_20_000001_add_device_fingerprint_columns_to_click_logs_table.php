<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes de Device Fingerprint anti-fraude a la table click_logs.
 *
 * - device_id       : hash SHA-256 de l'empreinte materielle du visiteur (fingerprint)
 * - is_vpn          : flag indiquant un trafic VPN/datacenter/proxy
 * - user_agent_hash : hash SHA-256 du User-Agent brut (detection multi-navigateur)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->string('device_id', 64)->nullable()->after('ip_address');
            $table->boolean('is_vpn')->default(false)->after('clicker_country');
            $table->string('user_agent_hash', 64)->nullable()->after('device_id');

            // Index pour deduplication device fingerprint par campagne
            $table->index(['device_id', 'is_valid', 'smart_link_id'], 'click_logs_device_dedup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->dropIndex('click_logs_device_dedup_idx');
            $table->dropColumn(['device_id', 'is_vpn', 'user_agent_hash']);
        });
    }
};
