<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            // Pourcentage retenu par l'organisateur sur chaque versement (0 = aucun)
            $table->decimal('organizer_fee_percent', 5, 2)->default(0)->after('period_type');
        });

        Schema::table('payouts', function (Blueprint $table) {
            // Photographie au moment du versement : le rapport reste exact même si les règles changent plus tard
            $table->decimal('fee_percent', 5, 2)->default(0)->after('amount');
            $table->decimal('fee_amount', 12, 2)->default(0)->after('fee_percent');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', fn (Blueprint $t) => $t->dropColumn(['fee_percent', 'fee_amount']));
        Schema::table('tontines', fn (Blueprint $t) => $t->dropColumn('organizer_fee_percent'));
    }
};
