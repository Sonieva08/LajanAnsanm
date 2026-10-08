<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('members', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('phone')->nullable();
        $table->unsignedInteger('position')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->unique(['tontine_id', 'position']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
