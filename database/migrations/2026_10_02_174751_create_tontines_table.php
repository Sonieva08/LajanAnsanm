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
    Schema::create('tontines', function (Blueprint $table) {
        $table->id();
        $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
        $table->string('name');
        $table->decimal('amount_per_period', 12, 2);
        $table->enum('period_type', ['weekly', 'monthly']);
        $table->date('start_date');
        $table->enum('status', ['draft', 'active', 'completed', 'paused'])->default('draft');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tontines');
    }
};
