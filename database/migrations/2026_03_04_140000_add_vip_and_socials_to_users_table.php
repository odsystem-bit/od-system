<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les champs VIP, liens sociaux et compteurs d'abonnes
 * a la table users pour le systeme Social Commerce.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // --- Statut VIP ---
            $table->boolean('is_vip')->default(false)->after('ifu_or_rccm');

            // --- Liens sociaux (nullable) ---
            $table->string('tiktok_url')->nullable()->after('is_vip');
            $table->string('instagram_url')->nullable()->after('tiktok_url');
            $table->string('facebook_url')->nullable()->after('instagram_url');
            $table->string('youtube_url')->nullable()->after('facebook_url');
            $table->string('snapchat_url')->nullable()->after('youtube_url');

            // --- Compteurs d'abonnes declares ---
            $table->unsignedInteger('tiktok_followers')->default(0)->after('snapchat_url');
            $table->unsignedInteger('instagram_followers')->default(0)->after('tiktok_followers');
            $table->unsignedInteger('facebook_followers')->default(0)->after('instagram_followers');
            $table->unsignedInteger('youtube_followers')->default(0)->after('facebook_followers');
            $table->unsignedInteger('snapchat_followers')->default(0)->after('youtube_followers');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'is_vip',
                'tiktok_url', 'instagram_url', 'facebook_url', 'youtube_url', 'snapchat_url',
                'tiktok_followers', 'instagram_followers', 'facebook_followers', 'youtube_followers', 'snapchat_followers',
            ]);
        });
    }
};
