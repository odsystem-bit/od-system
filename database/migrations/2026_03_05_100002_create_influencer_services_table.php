<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('influencer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->string('type');          // ugc_humain, video_pub_ia
            $table->decimal('price', 10, 2);
            $table->string('duration');      // 15s, 30s, 60s, long
            $table->text('description');

            $table->timestamps();

            $table->index(['influencer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_services');
    }
};
