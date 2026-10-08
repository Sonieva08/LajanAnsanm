<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->unsignedInteger('current_round')->default(1)->after('status');
        });

        // 1) nouvel index unique (commence par tontine_id : la clé étrangère peut s'y appuyer)
        Schema::table('cycles', function (Blueprint $table) {
            $table->unsignedInteger('round')->default(1)->after('tontine_id');
            $table->unique(['tontine_id', 'round', 'cycle_number']);
        });

        // 2) puis suppression de l'ancien
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropUnique(['tontine_id', 'cycle_number']);
        });
    }

    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->unique(['tontine_id', 'cycle_number']);
        });
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropUnique(['tontine_id', 'round', 'cycle_number']);
            $table->dropColumn('round');
        });
        Schema::table('tontines', function (Blueprint $table) {
            $table->dropColumn('current_round');
        });
    }
};
