<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_requests', function (Blueprint $table) {
            $table->string('payer_phone', 40)->nullable()->change();
            $table->string('transaction_ref', 80)->nullable()->change();
        });

        Schema::table('payment_declarations', function (Blueprint $table) {
            $table->string('payer_phone', 40)->nullable()->change();
            $table->string('transaction_ref', 80)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_requests', function (Blueprint $table) {
            $table->string('payer_phone', 40)->nullable(false)->change();
            $table->string('transaction_ref', 80)->nullable(false)->change();
        });

        Schema::table('payment_declarations', function (Blueprint $table) {
            $table->string('payer_phone', 40)->nullable(false)->change();
            $table->string('transaction_ref', 80)->nullable(false)->change();
        });
    }
};
