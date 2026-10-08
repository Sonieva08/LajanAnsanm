<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    // Approuve une demande payée. Renvoie la souscription, ou null si la demande n'est plus en attente.
    public function approve(SubscriptionRequest $request, User $owner, ?Carbon $endsAt = null, ?string $note = null): ?Subscription
    {
        return DB::transaction(function () use ($request, $owner, $endsAt, $note) {
            $r = SubscriptionRequest::whereKey($request->id)->lockForUpdate()->first();

            if (! $r || $r->status !== 'pending') {
                return null;
            }

            $current = $r->user->subscriptions()->where('status', 'active')->orderByDesc('ends_at')->first();

            // Renouvellement du même plan encore valable : on prolonge à partir de la fin actuelle
            $start = ($current && $current->plan_id === $r->plan_id && $current->ends_at->gt(now())) ? $current->ends_at->copy() : now();
            $ends = $endsAt ?? $start->copy()->addMonthsNoOverflow(Plan::PERIODS[$r->billing_period]);

            $current?->update(['status' => 'replaced']);

            $sub = Subscription::create([
                'user_id' => $r->user_id, 'plan_id' => $r->plan_id, 'status' => 'active',
                'billing_period' => $r->billing_period, 'starts_at' => $start, 'ends_at' => $ends,
                'amount' => $r->amount, 'source' => 'request', 'note' => $note, 'granted_by' => $owner->id,
            ]);

            $r->update([
                'status' => 'approved', 'reviewed_by' => $owner->id, 'reviewed_at' => now(),
                'review_note' => $note, 'subscription_id' => $sub->id,
            ]);

            ActivityLog::record('subscription_activated', $owner, ['plan' => $r->plan->name, 'amount' => (float) $r->amount, 'user_id' => $r->user_id]);

            return $sub;
        });
    }

    public function reject(SubscriptionRequest $request, User $owner, string $note): bool
    {
        return DB::transaction(function () use ($request, $owner, $note) {
            $r = SubscriptionRequest::whereKey($request->id)->lockForUpdate()->first();

            if (! $r || $r->status !== 'pending') {
                return false;
            }

            $r->update(['status' => 'rejected', 'reviewed_by' => $owner->id, 'reviewed_at' => now(), 'review_note' => $note]);
            ActivityLog::record('subscription_rejected', $owner, ['plan' => $r->plan->name, 'user_id' => $r->user_id]);

            return true;
        });
    }

    // Le propriétaire donne ou prolonge un abonnement sans demande de paiement
    public function grant(User $user, Plan $plan, Carbon $endsAt, User $owner, ?string $note = null): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $endsAt, $owner, $note) {
            $user->subscriptions()->where('status', 'active')->update(['status' => 'replaced']);

            $sub = Subscription::create([
                'user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(),
                'ends_at' => $endsAt, 'amount' => 0, 'source' => 'owner_grant', 'note' => $note, 'granted_by' => $owner->id,
            ]);

            ActivityLog::record('subscription_granted', $owner, ['plan' => $plan->name, 'user_id' => $user->id]);

            return $sub;
        });
    }

    // Retour au plan gratuit
    public function cancel(User $user, User $owner): void
    {
        $count = $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        if ($count > 0) {
            ActivityLog::record('subscription_cancelled', $owner, ['user_id' => $user->id]);
        }
    }
}
