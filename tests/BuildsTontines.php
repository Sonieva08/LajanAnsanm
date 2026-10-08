<?php

namespace Tests\Concerns;

use App\Models\Cycle;
use App\Models\Member;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Testing\TestResponse;

trait BuildsTontines
{
    protected function makeTontine(?User $admin = null, int $members = 3, array $attrs = []): Tontine
    {
        $admin ??= User::factory()->create();

        $tontine = Tontine::create(array_merge([
            'admin_id' => $admin->id,
            'name' => 'Sòl Test',
            'amount_per_period' => 500,
            'period_type' => 'weekly',
            'start_date' => today(),
            'status' => 'draft',
        ], $attrs));

        for ($i = 1; $i <= $members; $i++) {
            $tontine->members()->create([
                'name' => "Membre $i",
                'phone' => "+509 3700000$i",
                'position' => $i,
            ]);
        }

        return $tontine->load('admin');
    }

    protected function start(Tontine $t): Tontine
    {
        $this->actingAs($t->admin)
            ->post(route('tontines.cycles.generate', $t))
            ->assertSessionHasNoErrors();

        return $t->fresh()->load('admin');
    }

    protected function openCycle(Tontine $t): Cycle
    {
        return $t->fresh()->roundCycles()->where('status', 'open')->firstOrFail();
    }

    protected function pay(Tontine $t, Cycle $c, Member $m, array $extra = []): TestResponse
    {
        return $this->actingAs($t->admin)->post(
            route('tontines.cycles.contributions.store', [$t, $c]),
            array_merge([
                'member_id' => $m->id,
                'payment_method' => 'cash',
                'paid_at' => today()->format('Y-m-d'),
            ], $extra)
        );
    }

    protected function payAll(Tontine $t, Cycle $c): void
    {
        foreach ($t->members()->where('is_active', true)->get() as $m) {
            $this->pay($t, $c, $m)->assertSessionHasNoErrors();
        }
    }

    protected function payout(Tontine $t, Cycle $c, bool $confirm = false): TestResponse
    {
        return $this->actingAs($t->admin)->post(
            route('tontines.cycles.payout.store', [$t, $c]),
            $confirm ? ['confirm_incomplete' => 1] : []
        );
    }

    // Joue tous les tours du kòl en cours : tout le monde paie, le bénéficiaire est versé
    protected function complete(Tontine $t): Tontine
    {
        for ($guard = 0; $guard < 50; $guard++) {
            $t = $t->fresh()->load('admin');

            if ($t->status !== 'active') {
                break;
            }

            $cycle = $this->openCycle($t);
            $this->payAll($t, $cycle);
            $this->payout($t, $cycle)->assertSessionHasNoErrors();
        }

        return $t->fresh()->load('admin');
    }
}
