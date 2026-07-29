<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(false);
            $table->text('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('environment')->default('sandbox');
            $table->timestamps();
        });

        // Seed the two gateways
        DB::table('gateways')->insert([
            [
                'name'        => 'FedaPay',
                'slug'        => 'fedapay',
                'is_active'   => true,
                'public_key'  => null,
                'secret_key'  => null,
                'environment' => 'sandbox',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'PayDunya',
                'slug'        => 'paydunya',
                'is_active'   => false,
                'public_key'  => null,
                'secret_key'  => null,
                'environment' => 'sandbox',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
