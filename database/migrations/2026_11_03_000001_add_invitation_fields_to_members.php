<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->timestamp('invited_at')->nullable()->after('consent_at');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invited_at');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['email', 'invited_at', 'invitation_accepted_at']);
        });
    }
};
