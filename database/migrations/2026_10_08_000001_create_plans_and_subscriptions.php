<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('badge')->nullable();
            $table->boolean('is_highlighted')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_free')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('price_monthly', 12, 2)->nullable();
            $table->decimal('price_quarterly', 12, 2)->nullable();
            $table->decimal('price_yearly', 12, 2)->nullable();
            $table->unsignedInteger('max_active_tontines')->nullable();      // null = illimité
            $table->unsignedInteger('max_members_per_tontine')->nullable();  // null = illimité
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('active');          // active | replaced | cancelled
            $table->string('billing_period')->nullable();          // monthly | quarterly | yearly
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('source')->default('request');          // request | owner_grant
            $table->text('note')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status', 'ends_at']);
        });

        Schema::create('subscription_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('billing_period');
            $table->decimal('amount', 12, 2);                       // prix figé au moment de la demande
            $table->string('payment_method');                       // moncash | natcash | bank
            $table->string('payer_phone', 40);
            $table->string('transaction_ref', 80);
            $table->string('reference_code', 30)->unique();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');           // pending | approved | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        // Les tontines existantes restent utilisables telles quelles : hors limites du plan
        Schema::table('tontines', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(false)->after('status');
        });
        DB::table('tontines')->update(['is_legacy' => true]);

        // Plans de départ. Les plans payants restent INACTIFS tant que le propriétaire n'a pas fixé les prix.
        $now = now();
        $all = json_encode(['csv_export', 'restart_round', 'whatsapp_reminders', 'organizer_fee']);
        DB::table('plans')->insert([
            ['slug' => 'gratis', 'name' => 'Gratis', 'description' => 'Pou kòmanse premye tontin ou.', 'badge' => null, 'is_highlighted' => false,
             'is_active' => true, 'is_free' => true, 'sort_order' => 1, 'price_monthly' => 0, 'max_active_tontines' => 1,
             'max_members_per_tontine' => 10, 'features' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'esansyel', 'name' => 'Esansyèl', 'description' => 'Pou òganizatè ki jere plizyè tontin.', 'badge' => null, 'is_highlighted' => false,
             'is_active' => false, 'is_free' => false, 'sort_order' => 2, 'price_monthly' => null, 'max_active_tontines' => 3,
             'max_members_per_tontine' => 25, 'features' => json_encode(['csv_export', 'restart_round', 'whatsapp_reminders']), 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'pro', 'name' => 'Pro', 'description' => 'Pou gwo gwoup ak òganizatè aktif.', 'badge' => 'Pi popilè', 'is_highlighted' => true,
             'is_active' => false, 'is_free' => false, 'sort_order' => 3, 'price_monthly' => null, 'max_active_tontines' => 10,
             'max_members_per_tontine' => 50, 'features' => $all, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'oganizasyon', 'name' => 'Òganizasyon', 'description' => 'Pou kooperativ ak gwo òganizasyon.', 'badge' => null, 'is_highlighted' => false,
             'is_active' => false, 'is_free' => false, 'sort_order' => 4, 'price_monthly' => null, 'max_active_tontines' => null,
             'max_members_per_tontine' => 100, 'features' => $all, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('tontines', fn (Blueprint $t) => $t->dropColumn('is_legacy'));
        Schema::dropIfExists('subscription_requests');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
