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
    Schema::create('payouts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cycle_id')->unique()->constrained()->cascadeOnDelete();
        $table->foreignId('member_id')->constrained()->cascadeOnDelete();
        $table->decimal('amount', 12, 2);
        $table->date('paid_at')->nullable();
        $table->boolean('confirmed')->default(false);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
