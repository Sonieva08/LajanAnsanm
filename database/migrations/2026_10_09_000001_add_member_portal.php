<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            // Le créateur choisit tontine par tontine d'ouvrir ou non un espace aux membres
            $table->boolean('member_portal_enabled')->default(false)->after('status');
            // Où les membres envoient leur cotisation (MonCash, NatCash, banque) : saisi par le créateur
            $table->json('portal_payment')->nullable()->after('member_portal_enabled');
        });

        Schema::table('members', function (Blueprint $table) {
            // Le jeton est chiffré (le créateur peut recopier le lien) ; le hash sert à la recherche
            $table->text('access_token')->nullable()->after('is_active');
            $table->string('access_token_hash', 64)->nullable()->unique()->after('access_token');
            $table->timestamp('last_portal_visit_at')->nullable()->after('access_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['access_token_hash']);
            $table->dropColumn(['access_token', 'access_token_hash', 'last_portal_visit_at']);
        });

        Schema::table('tontines', function (Blueprint $table) {
            $table->dropColumn(['member_portal_enabled', 'portal_payment']);
        });
    }
};
