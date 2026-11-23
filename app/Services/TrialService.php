<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrialService
{
    public const MONTHS = 3;

    // Donne l'essai Pro à un nouveau compte. Renvoie false si ce n'est pas permis.
    public function grant(User $user): bool
    {
        if ($user->is_owner) {
            return false;
        }

        $plan = Plan::where('slug', 'pro')->first();
        if (! $plan) {
            return false;
        }

        $emailHash = self::emailHash($user->email);
        $phoneHash = self::phoneHash($user->phone);

        return DB::transaction(function () use ($user, $plan, $emailHash, $phoneHash) {
            if (DB::table('trial_claims')->where('email_hash', $emailHash)->exists()) {
                return false;
            }
            if ($phoneHash && DB::table('trial_claims')->where('phone_hash', $phoneHash)->exists()) {
                return false;
            }
            if ($user->subscriptions()->exists()) {
                return false;
            }

            Subscription::create([
                'user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(),
                'ends_at' => now()->addMonths(self::MONTHS), 'amount' => 0, 'source' => 'trial',
            ]);

            DB::table('trial_claims')->insert([
                'user_id' => $user->id, 'email_hash' => $emailHash, 'phone_hash' => $phoneHash,
                'granted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);

            ActivityLog::record('subscription_granted', $user, ['plan' => $plan->name]);

            return true;
        });
    }

    public static function emailHash(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    public static function phoneHash(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return strlen($digits) >= 8 ? hash('sha256', $digits) : null;
    }
}
