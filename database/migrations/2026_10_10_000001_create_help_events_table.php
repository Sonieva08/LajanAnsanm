<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Journal anonyme : aucune identité, aucun numéro. Sert à savoir quelles questions les gens se posent.
        Schema::create('help_events', function (Blueprint $table) {
            $table->id();
            $table->string('audience', 10);          // creator | member
            $table->string('type', 10)->index();     // open | search | miss
            $table->string('faq_id', 40)->nullable()->index();
            $table->string('query', 120)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_events');
    }
};
