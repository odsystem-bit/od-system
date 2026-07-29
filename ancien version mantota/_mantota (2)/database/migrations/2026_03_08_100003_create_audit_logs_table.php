<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // approve_kyc, reject_kyc, resolve_dispute, approve_withdrawal, etc.
            $table->string('model_type', 50); // User, Order, Dispute, ServiceOrder, etc.
            $table->unsignedBigInteger('model_id');
            $table->json('old_values')->nullable(); // Anciennes valeurs avant modification
            $table->json('new_values')->nullable(); // Nouvelles valeurs après modification
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes pour performance
            $table->index(['admin_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
