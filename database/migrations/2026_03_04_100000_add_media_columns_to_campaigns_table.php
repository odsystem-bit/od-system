<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes media a la table campaigns.
 *
 *  - media_path : chemin du fichier stocke (image ou video promotionnelle).
 *  - media_type : type de media ('image' ou 'video').
 *
 * Ces colonnes permettent au vendeur d'uploader directement
 * le contenu que les créateurs de contenu devront promouvoir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('media_path')->nullable()->after('target_url');
            $table->enum('media_type', ['image', 'video'])->nullable()->after('media_path');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['media_path', 'media_type']);
        });
    }
};
