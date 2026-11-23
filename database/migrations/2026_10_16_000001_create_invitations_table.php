<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique(); // on ne stocke jamais le lien en clair
            $table->string('phone', 30)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        // Lien entre un membre de tontine et le compte de la personne
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['tontine_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['tontine_id', 'user_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::dropIfExists('invitations');
    }
};
