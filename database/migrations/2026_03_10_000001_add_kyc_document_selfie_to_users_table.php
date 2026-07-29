<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne kyc_document_selfie a la table users.
 *
 *  - kyc_document_selfie : photo de la personne tenant sa carte d'identite
 *                          en main (preuve de possession physique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kyc_document_selfie')->nullable()->after('kyc_document_back');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kyc_document_selfie');
        });
    }
};
