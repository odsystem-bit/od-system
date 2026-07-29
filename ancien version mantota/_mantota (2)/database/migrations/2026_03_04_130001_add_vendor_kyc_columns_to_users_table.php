<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes KYC business specifiques au vendeur.
 *
 *  - business_name  : nom commercial ou raison sociale.
 *  - ifu_or_rccm    : numero IFU ou RCCM (optionnel, entreprises formelles).
 *
 * Ces colonnes completent les colonnes kyc_document_front/back
 * deja presentes pour creer un KYC differencie par role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_name')->nullable()->after('kyc_document_back');
            $table->string('ifu_or_rccm')->nullable()->after('business_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['business_name', 'ifu_or_rccm']);
        });
    }
};
