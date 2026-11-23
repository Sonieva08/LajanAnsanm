<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Paiements d'abonnement faits EN LIGNE (passerelle). Table séparée de subscription_requests :
    // une tentative abandonnée ne doit jamais encombrer la file de validation manuelle du propriétaire.
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('billing_period', 20);                    // monthly | quarterly | yearly
            $table->decimal('amount', 12, 2);                        // prix figé au moment du paiement
            $table->string('provider', 30)->default('moncashconnect');
            $table->string('reference_id', 100)->unique();           // notre identifiant, envoyé à la passerelle
            $table->string('status', 20)->default('pending');        // pending | completed | failed | review
            $table->text('payment_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('failure_reason', 120)->nullable();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
