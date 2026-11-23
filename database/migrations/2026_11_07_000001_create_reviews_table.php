<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Un seul avis par utilisateur (il peut le modifier ou le supprimer)
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');          // 1 à 5
            $table->string('comment', 500);
            $table->string('status', 10)->default('pending'); // pending | approved | hidden
            $table->timestamps();

            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
