<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // KYC Robot — champs declares par l'utilisateur lors de la soumission KYC
            $table->date('birth_date')->nullable()->after('kyc_document_selfie');
            $table->date('id_card_expiry')->nullable()->after('birth_date');

            // Suivi de la mise a jour des stats sociales (resubmission tous les 2 mois)
            $table->timestamp('socials_updated_at')->nullable()->after('snapchat_followers');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'id_card_expiry', 'socials_updated_at']);
        });
    }
};
