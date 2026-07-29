<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index();     // login_success, login_failed, brute_force_detected, suspicious_login, ip_blocked, webhook_suspicious, unauthorized_access
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->index();
            $table->string('email', 255)->nullable();       // attempted email (for failed logins)
            $table->string('user_agent', 500)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('guard', 20)->nullable();        // admin, vendor, influencer
            $table->json('metadata')->nullable();            // extra context data
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason', 255);
            $table->boolean('is_permanent')->default(false);
            $table->unsignedBigInteger('blocked_by')->nullable(); // admin_id for manual blocks
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('blocked_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('security_events');
    }
};
