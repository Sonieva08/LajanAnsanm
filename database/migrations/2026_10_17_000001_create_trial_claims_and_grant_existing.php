<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Une seule trace par adresse e-mail (et par téléphone si connu) : pas d'essai à répétition
        Schema::create('trial_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email_hash', 64)->unique();
            $table->string('phone_hash', 64)->nullable()->unique();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();
        });

        // Anciens utilisateurs sans abonnement : 3 mois à partir du lancement
        $pro = DB::table('plans')->where('slug', 'pro')->value('id');
        if (! $pro) {
            return;
        }

        $ends = now()->addMonths(3);

        DB::table('users')
            ->where('is_owner', false)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('subscriptions')->whereColumn('subscriptions.user_id', 'users.id'))
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($pro, $ends) {
                foreach ($users as $u) {
                    DB::table('subscriptions')->insert([
                        'user_id' => $u->id, 'plan_id' => $pro, 'status' => 'active', 'starts_at' => now(),
                        'ends_at' => $ends, 'amount' => 0, 'source' => 'trial_launch',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('trial_claims')->insert([
                        'user_id' => $u->id, 'email_hash' => hash('sha256', mb_strtolower(trim($u->email))),
                        'phone_hash' => null, 'granted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('subscriptions')->where('source', 'trial_launch')->delete();
        Schema::dropIfExists('trial_claims');
    }
};
