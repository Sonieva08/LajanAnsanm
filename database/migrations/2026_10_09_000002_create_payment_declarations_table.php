<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Un membre DÉCLARE son paiement ; la cotisation réelle n'existe qu'une fois validée par le créateur.
    // Table séparée volontairement : les totaux, versements et rapports ne comptent jamais une déclaration non validée.
    public function up(): void
    {
        Schema::create('payment_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);                      // fixé par le serveur, jamais par le navigateur
            $table->string('payment_method', 20);                   // moncash | natcash | bank
            $table->string('payer_phone', 40);
            $table->string('transaction_ref', 80);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending');       // pending | approved | rejected
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('contribution_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['cycle_id', 'member_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_declarations');
    }
};
