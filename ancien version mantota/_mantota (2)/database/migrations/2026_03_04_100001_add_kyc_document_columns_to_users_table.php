<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes de stockage des documents KYC a la table users.
 *
 *  - kyc_document_front : chemin du recto de la piece d'identite.
 *  - kyc_document_back  : chemin du verso de la piece d'identite.
 *
 * Les fichiers sont stockes sur le disque 'local' (non public)
 * pour des raisons de securite et de confidentialite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kyc_document_front')->nullable()->after('kyc_status');
            $table->string('kyc_document_back')->nullable()->after('kyc_document_front');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_document_front', 'kyc_document_back']);
        });
    }
};
