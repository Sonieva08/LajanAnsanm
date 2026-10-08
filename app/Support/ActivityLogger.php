<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Contribution;
use App\Models\Payout;
use App\Models\Tontine;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

/**
 * Enregistre les actions clés sans toucher aux contrôleurs.
 * RÈGLE : ne jamais mettre de nom de membre ni de téléphone dans "meta".
 */
class ActivityLogger
{
    public static function register(): void
    {
        Event::listen(Registered::class, fn ($e) => ActivityLog::record('register', $e->user));
        Event::listen(Login::class, fn ($e) => ActivityLog::record('login', $e->user));

        Tontine::created(fn (Tontine $t) => ActivityLog::record(
            'tontine_created', Auth::user() ?? $t->admin,
            ['amount' => (float) $t->amount_per_period, 'fee_percent' => (float) $t->organizer_fee_percent],
            $t->id
        ));

        Tontine::deleted(fn (Tontine $t) => ActivityLog::record('tontine_deleted', Auth::user(), [], $t->id));

        Tontine::updated(function (Tontine $t) {
            $user = Auth::user() ?? $t->admin;

            if ($t->wasChanged('current_round')) {
                ActivityLog::record('round_restarted', $user, ['round' => $t->current_round], $t->id);
            } elseif ($t->wasChanged('status')) {
                match ($t->status) {
                    'active' => ActivityLog::record('calendar_generated', $user, [
                        'round' => $t->current_round, 'cycles' => $t->roundCycles()->count(),
                    ], $t->id),
                    'completed' => ActivityLog::record('tontine_completed', $user, ['round' => $t->current_round], $t->id),
                    'draft' => ActivityLog::record('calendar_cancelled', $user, [], $t->id),
                    default => null,
                };
            }
        });

        Contribution::created(fn (Contribution $c) => ActivityLog::record(
            'contribution_recorded', Auth::user(),
            ['amount' => (float) $c->amount, 'method' => $c->payment_method], $c->cycle?->tontine_id
        ));

        Contribution::deleted(fn (Contribution $c) => ActivityLog::record(
            'contribution_cancelled', Auth::user(), ['amount' => (float) $c->amount], $c->cycle?->tontine_id
        ));

        Payout::created(fn (Payout $p) => ActivityLog::record(
            'payout_made', Auth::user(),
            ['gross' => (float) $p->amount, 'fee' => (float) $p->fee_amount, 'net' => $p->netAmount()],
            $p->cycle?->tontine_id
        ));
    }
}
