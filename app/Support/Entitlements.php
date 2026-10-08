<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ce que le compte d'un utilisateur a le droit de faire, selon son abonnement.
 * États : owner | free (jamais abonné) | active | grace (expiré depuis moins de N jours) | expired (retour au plan gratuit)
 */
final class Entitlements
{
    public function __construct(
        public readonly User $user,
        public readonly Plan $plan,
        public readonly string $state,
        public readonly ?Subscription $subscription,
        public readonly int $graceDays,
    ) {}

    public static function for(User $user): self
    {
        $grace = Setting::subscriptionRules()['grace_days'];

        if ($user->is_owner) {
            $plan = new Plan(['name' => 'Pwopriyetè', 'max_active_tontines' => null, 'max_members_per_tontine' => null, 'features' => Features::keys()]);

            return new self($user, $plan, 'owner', null, $grace);
        }

        $sub = $user->subscriptions()->where('status', 'active')->with('plan')->orderByDesc('ends_at')->first();

        if (! $sub) {
            return new self($user, Plan::free(), 'free', null, $grace);
        }

        if ($sub->ends_at->gte(now())) {
            return new self($user, $sub->plan, 'active', $sub, $grace);
        }

        if ($sub->ends_at->copy()->addDays($grace)->gte(now())) {
            return new self($user, $sub->plan, 'grace', $sub, $grace);
        }

        return new self($user, Plan::free(), 'expired', $sub, $grace);
    }

    public function isPaid(): bool
    {
        return in_array($this->state, ['active', 'grace'], true);
    }

    public function has(string $feature): bool
    {
        return $this->state === 'owner' || $this->plan->allows($feature);
    }

    public function maxTontines(): ?int
    {
        return $this->plan->max_active_tontines;
    }

    public function maxMembers(): ?int
    {
        return $this->plan->max_members_per_tontine;
    }

    // Tontines qui comptent dans la limite : ni terminées, ni antérieures au lancement des plans
    private function counted(): Builder
    {
        return Tontine::where('admin_id', $this->user->id)->where('is_legacy', false)->where('status', '!=', 'completed');
    }

    public function activeTontines(): int
    {
        return $this->counted()->count();
    }

    public function canCreateTontine(): bool
    {
        $max = $this->maxTontines();

        return $this->state === 'owner' || $max === null || $this->activeTontines() < $max;
    }

    public function canAddMember(Tontine $t): bool
    {
        $max = $this->maxMembers();

        return $this->state === 'owner' || $t->is_legacy || $max === null
            || $t->members()->where('is_active', true)->count() < $max;
    }

    // Une tontine est modifiable sauf si elle dépasse les limites du plan actuel (lecture seule)
    public function isWritable(Tontine $t): bool
    {
        if ($this->state === 'owner' || $t->is_legacy || $t->status === 'completed') {
            return true;
        }

        $maxMembers = $this->maxMembers();
        if ($maxMembers !== null && $t->members()->where('is_active', true)->count() > $maxMembers) {
            return false;
        }

        $maxTontines = $this->maxTontines();
        if ($maxTontines !== null) {
            $allowed = $this->counted()->orderBy('id')->limit($maxTontines)->pluck('id')->all();

            return in_array($t->id, $allowed, true);
        }

        return true;
    }

    public function readOnlyCount(): int
    {
        return $this->counted()->get()->reject(fn (Tontine $t) => $this->isWritable($t))->count();
    }

    public function daysLeft(): ?int
    {
        return $this->state === 'active' ? (int) ceil(now()->diffInDays($this->subscription->ends_at, false)) : null;
    }

    public function graceDaysLeft(): ?int
    {
        return $this->state === 'grace'
            ? (int) ceil(now()->diffInDays($this->subscription->ends_at->copy()->addDays($this->graceDays), false))
            : null;
    }

    // Prochain plan payant qui lève la limite ou donne la fonction demandée
    public function recommendedPlan(string $need): ?Plan
    {
        return Plan::listed()->where('is_free', false)->get()->first(function (Plan $p) use ($need) {
            return match ($need) {
                'tontines' => $this->maxTontines() !== null && ($p->max_active_tontines === null || $p->max_active_tontines > $this->maxTontines()),
                'members' => $this->maxMembers() !== null && ($p->max_members_per_tontine === null || $p->max_members_per_tontine > $this->maxMembers()),
                default => $p->allows($need),
            };
        });
    }
}
