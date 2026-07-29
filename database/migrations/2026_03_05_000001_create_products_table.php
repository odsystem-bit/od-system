<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('vendor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('type');                         // 'physical' | 'digital'
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->decimal('commission_percent', 5, 2);    // % reverse au créateur de contenu
            $table->unsignedInteger('stock')->nullable();   // Produits physiques uniquement
            $table->string('access_url', 2048)->nullable(); // Produits digitaux uniquement
            $table->string('image_path')->nullable();       // Image de couverture

            $table->timestamps();

            $table->index(['vendor_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
