<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;

// Règle unique d'activation d'un abonnement payé en ligne. Appelée par le webhook ET par la page de retour :
// les deux peuvent arriver en même temps, donc tout passe par un verrou et reste idempotent.
class OnlinePaymentService
{
    public function __construct(private SubscriptionService $subscriptions) {}

    /**
     * @param  'completed'|'failed'  $outcome
     * @param  int|null  $amount  montant HTG confirmé par la passerelle
     * @return 'activated'|'already'|'review'|'failed'
     */
    public function settle(SubscriptionPayment $payment, string $outcome, ?int $amount = null, ?string $reason = null): string
    {
        return DB::transaction(function () use ($payment, $outcome, $amount, $reason) {
            $p = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->first();

            if ($p->status === 'completed') {
                return 'already';
            }

            if ($outcome === 'failed') {
                // Un paiement déjà en vérification manuelle ne repasse pas en échec tout seul
                if ($p->status === 'pending') {
                    $p->update(['status' => 'failed', 'failure_reason' => $reason ? mb_substr($reason, 0, 120) : null]);
                }

                return 'failed';
            }

            // « completed » : l'argent a été pris. On active même si la page avait expiré ou était marquée échouée.
            if ($amount === null || $amount !== (int) round((float) $p->amount)) {
                $p->update(['status' => 'review', 'failure_reason' => 'amount_mismatch']);
                ActivityLog::record('subscription_payment_review', $p->user, [
                    'plan' => $p->plan->name, 'amount' => (float) $p->amount, 'paid' => $amount, 'user_id' => $p->user_id,
                ]);

                return 'review';
            }

            $sub = $this->subscriptions->activateFromPayment($p);

            $p->update(['status' => 'completed', 'completed_at' => now(), 'subscription_id' => $sub->id, 'failure_reason' => null]);

            ActivityLog::record('subscription_activated', $p->user, [
                'plan' => $p->plan->name, 'amount' => (float) $p->amount, 'user_id' => $p->user_id, 'online' => true,
            ]);

            return 'activated';
        });
    }
}
