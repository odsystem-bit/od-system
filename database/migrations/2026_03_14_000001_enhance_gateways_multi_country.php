<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes pour le routage multi-pays et multi-passerelle :
 *  - countries      : JSON array de codes ISO pays couverts (ex: ["BJ","CI","SN"])
 *  - payin_fee      : frais de collecte en %
 *  - payout_fee     : frais de transfert/payout en %
 *  - priority       : ordre de priorité (1 = prioritaire)
 *  - supports_refund: la passerelle supporte les remboursements API
 *  - supports_payout: la passerelle supporte les transferts/payout API
 *  - extra_config   : JSON pour config spécifique (ex: apikey, site_id, etc.)
 *
 * Ajoute aussi les 3 nouvelles passerelles : FeexPay, CinetPay, Flutterwave.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->json('countries')->nullable()->after('environment');
            $table->decimal('payin_fee', 5, 2)->default(0)->after('countries');
            $table->decimal('payout_fee', 5, 2)->default(0)->after('payin_fee');
            $table->unsignedSmallInteger('priority')->default(10)->after('payout_fee');
            $table->boolean('supports_refund')->default(false)->after('priority');
            $table->boolean('supports_payout')->default(false)->after('supports_refund');
            $table->json('extra_config')->nullable()->after('supports_payout');
        });

        // Ajouter payment_gateway aux transactions (dépôts)
        if (! Schema::hasColumn('transactions', 'payment_gateway')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('payment_gateway', 30)->nullable()->after('gateway_ref');
            });
        }

        // Mettre à jour les passerelles existantes avec les pays et frais
        DB::table('gateways')->where('slug', 'fedapay')->update([
            'countries'       => json_encode(['BJ', 'TG', 'CI', 'SN']),
            'payin_fee'       => 1.50,
            'payout_fee'      => 1.50,
            'priority'        => 2,
            'supports_refund' => false, // 401 en prod
            'supports_payout' => true,
        ]);

        DB::table('gateways')->where('slug', 'paydunya')->update([
            'countries'       => json_encode(['SN', 'BJ', 'CI', 'TG', 'BF', 'ML']),
            'payin_fee'       => 2.00,
            'payout_fee'      => 2.00,
            'priority'        => 5,
            'supports_refund' => true,
            'supports_payout' => true,
        ]);

        // Insérer les 3 nouvelles passerelles
        DB::table('gateways')->insert([
            [
                'name'            => 'FeexPay',
                'slug'            => 'feexpay',
                'is_active'       => false,
                'public_key'      => null,
                'secret_key'      => null,
                'webhook_secret'  => null,
                'environment'     => 'sandbox',
                'countries'       => json_encode(['BJ', 'TG']),
                'payin_fee'       => 1.70,
                'payout_fee'      => 1.00,
                'priority'        => 1, // Meilleur tarif au Bénin
                'supports_refund' => true,
                'supports_payout' => true,
                'extra_config'    => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'name'            => 'CinetPay',
                'slug'            => 'cinetpay',
                'is_active'       => false,
                'public_key'      => null,
                'secret_key'      => null,
                'webhook_secret'  => null,
                'environment'     => 'sandbox',
                'countries'       => json_encode(['CI', 'SN', 'TG', 'CM', 'GN', 'BJ', 'ML', 'BF', 'CD']),
                'payin_fee'       => 2.00,
                'payout_fee'      => 1.50,
                'priority'        => 3,
                'supports_refund' => false,
                'supports_payout' => true,
                'extra_config'    => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'name'            => 'Flutterwave',
                'slug'            => 'flutterwave',
                'is_active'       => false,
                'public_key'      => null,
                'secret_key'      => null,
                'webhook_secret'  => null,
                'environment'     => 'sandbox',
                'countries'       => json_encode(['NG', 'GH', 'KE', 'ZA', 'TZ', 'UG', 'RW', 'CM', 'CI', 'SN', 'BF', 'ML', 'GN', 'BJ']),
                'payin_fee'       => 1.40,
                'payout_fee'      => 1.00,
                'priority'        => 4,
                'supports_refund' => true,
                'supports_payout' => true,
                'extra_config'    => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('gateways')->whereIn('slug', ['feexpay', 'cinetpay', 'flutterwave'])->delete();

        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn([
                'countries', 'payin_fee', 'payout_fee', 'priority',
                'supports_refund', 'supports_payout', 'extra_config',
            ]);
        });
    }
};
