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
    Schema::create('contributions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
        $table->foreignId('member_id')->constrained()->cascadeOnDelete();
        $table->decimal('amount', 12, 2);
        $table->date('paid_at');
        $table->string('payment_method')->nullable(); // cash, moncash, natcash, bank
        $table->text('note')->nullable();
        $table->timestamps();

        $table->unique(['cycle_id', 'member_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
