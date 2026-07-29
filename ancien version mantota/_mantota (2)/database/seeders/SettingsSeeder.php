<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'withdrawal_fee_percent', 'value' => '20', 'type' => 'integer'],
            ['key' => 'min_withdrawal_amount', 'value' => '1000', 'type' => 'integer'],
            ['key' => 'ugc_studio_fee_percent', 'value' => '15', 'type' => 'integer'],
            ['key' => 'min_cpc_price', 'value' => '25', 'type' => 'integer'],
            ['key' => 'home_hero_title', 'value' => 'Vendez plus, plus vite.', 'type' => 'string'],
            // Frais passerelles
            ['key' => 'fedapay_fee_percent', 'value' => '1.5', 'type' => 'float'],
            ['key' => 'paydunya_fee_percent', 'value' => '2.0', 'type' => 'float'],
            ['key' => 'deposit_markup_percent', 'value' => '1.5', 'type' => 'float'],
            // Paliers de campagne
            ['key' => 'tier_argent_threshold', 'value' => '25000', 'type' => 'integer'],
            ['key' => 'tier_or_threshold', 'value' => '100000', 'type' => 'integer'],
            ['key' => 'tier_cost_bronze', 'value' => '2000', 'type' => 'integer'],
            ['key' => 'tier_cost_argent', 'value' => '5000', 'type' => 'integer'],
            ['key' => 'tier_cost_or', 'value' => '15000', 'type' => 'integer'],
            ['key' => 'tier_followers_bronze_min', 'value' => '1000', 'type' => 'integer'],
            ['key' => 'tier_followers_bronze_max', 'value' => '9999', 'type' => 'integer'],
            ['key' => 'tier_followers_argent_min', 'value' => '10000', 'type' => 'integer'],
            ['key' => 'tier_followers_argent_max', 'value' => '99999', 'type' => 'integer'],
            ['key' => 'tier_followers_or_min', 'value' => '100000', 'type' => 'integer'],
            ['key' => 'tier_followers_or_max', 'value' => '10000000', 'type' => 'integer'],
            // Videos YouTube
            ['key' => 'video_vendor_guide', 'value' => '', 'type' => 'string'],
            ['key' => 'video_influencer_guide', 'value' => '', 'type' => 'string'],
            ['key' => 'video_buyer_guide', 'value' => '', 'type' => 'string'],
            ['key' => 'video_welcome', 'value' => '', 'type' => 'string'],
            // Entreprise
            ['key' => 'company_name', 'value' => 'MANTOTA', 'type' => 'string'],
            ['key' => 'contact_email', 'value' => 'contact@mantota.com', 'type' => 'string'],
            ['key' => 'whatsapp_phone', 'value' => '+229 97 00 00 00', 'type' => 'string'],
            ['key' => 'rccm', 'value' => '', 'type' => 'string'],
            ['key' => 'ifu', 'value' => '', 'type' => 'string'],
            ['key' => 'physical_address', 'value' => '', 'type' => 'string'],
            // Reseaux sociaux
            ['key' => 'social_facebook', 'value' => '', 'type' => 'string'],
            ['key' => 'social_instagram', 'value' => '', 'type' => 'string'],
            ['key' => 'social_tiktok', 'value' => '', 'type' => 'string'],
            ['key' => 'social_twitter', 'value' => '', 'type' => 'string'],
            // Cles API passerelle de paiement (priorite sur .env)
            ['key' => 'fedapay_secret_key', 'value' => '', 'type' => 'string'],
            ['key' => 'fedapay_webhook_secret', 'value' => '', 'type' => 'string'],
            // PayDunya
            ['key' => 'paydunya_master_key', 'value' => '', 'type' => 'string'],
            ['key' => 'paydunya_private_key', 'value' => '', 'type' => 'string'],
            ['key' => 'paydunya_token', 'value' => '', 'type' => 'string'],
            ['key' => 'paydunya_webhook_secret', 'value' => '', 'type' => 'string'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(
                ['key' => $s['key']],
                ['value' => $s['value'], 'type' => $s['type']],
            );
        }
    }
}
