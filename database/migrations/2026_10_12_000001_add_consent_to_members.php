<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Date à laquelle l'organisateur a confirmé que le membre accepte la politique de confidentialité
            $table->timestamp('consent_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('members', fn (Blueprint $t) => $t->dropColumn('consent_at'));
    }
};
