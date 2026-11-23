<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('plans')->whereIn('slug', ['pro', 'oganizasyon'])->get() as $plan) {
            $features = json_decode((string) $plan->features, true) ?: [];

            if (! in_array('member_portal', $features, true)) {
                $features[] = 'member_portal';
                DB::table('plans')->where('id', $plan->id)->update(['features' => json_encode(array_values($features))]);
            }
        }
    }

    public function down(): void
    {
        // Pas de retour arrière : le propriétaire peut avoir modifié les plans depuis la console
    }
};
