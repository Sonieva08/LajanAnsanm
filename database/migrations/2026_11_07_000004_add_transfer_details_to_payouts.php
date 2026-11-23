<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('sender_method', 20)->nullable()->after('account_name');
            $table->string('sender_phone', 40)->nullable()->after('sender_method');
            $table->string('transaction_ref', 80)->nullable()->after('sender_phone');
            $table->text('note')->nullable()->after('transaction_ref');
            $table->string('receipt_path')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['sender_method', 'sender_phone', 'transaction_ref', 'note', 'receipt_path']);
        });
    }
};
