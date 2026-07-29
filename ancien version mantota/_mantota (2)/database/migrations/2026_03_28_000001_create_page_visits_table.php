<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('country', 100)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('page_url', 500);
            $table->string('referrer', 500)->nullable();
            $table->string('device_type', 20)->default('desktop');
            $table->string('browser', 50)->nullable();
            $table->unsignedInteger('time_spent')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
