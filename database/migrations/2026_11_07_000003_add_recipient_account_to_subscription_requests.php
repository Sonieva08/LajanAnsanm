<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_requests', function (Blueprint $table) {
            $table->string('recipient_account', 120)->nullable()->after('payer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_requests', function (Blueprint $table) {
            $table->dropColumn('recipient_account');
        });
    }
};
