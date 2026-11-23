<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comment le membre veut recevoir son tour (modifiable à tout moment)
        Schema::table('members', function (Blueprint $table) {
            $table->string('payout_method', 20)->nullable()->after('is_active');   // moncash | natcash | bank | cash
            $table->string('payout_account', 120)->nullable()->after('payout_method');
            $table->string('payout_name', 100)->nullable()->after('payout_account');
        });

        Schema::table('payouts', function (Blueprint $table) {
            // Photographie au moment du versement : le rapport reste exact même si le membre change ses coordonnées ensuite
            $table->string('method', 20)->nullable()->after('confirmed');
            $table->string('account', 120)->nullable()->after('method');
            $table->string('account_name', 100)->nullable()->after('account');
            // Réponse du bénéficiaire. null = pas encore répondu (ou versement antérieur au portail)
            $table->string('member_response', 20)->nullable()->after('account_name');   // received | not_received
            $table->timestamp('member_responded_at')->nullable()->after('member_response');
            $table->text('member_note')->nullable()->after('member_responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['method', 'account', 'account_name', 'member_response', 'member_responded_at', 'member_note']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['payout_method', 'payout_account', 'payout_name']);
        });
    }
};
