<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class CycleTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function deadlines($t): array
    {
        return $t->roundCycles()->get()->map(fn ($c) => $c->deadline->format('Y-m-d'))->all();
    }

    public function test_generation_creates_one_cycle_per_member_in_order(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['start_date' => '2026-10-02']));

        $cycles = $t->roundCycles()->get();
        $this->assertCount(3, $cycles);
        $this->assertSame([1, 2, 3], $cycles->pluck('cycle_number')->all());
        $this->assertSame(['open', 'pending', 'pending'], $cycles->pluck('status')->all());
        $this->assertSame('active', $t->status);

        // Le membre en position n reçoit au tour n
        foreach ($cycles as $c) {
            $this->assertSame($c->cycle_number, $c->beneficiary->position);
        }
    }

    public function test_weekly_deadlines_are_seven_days_apart(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['start_date' => '2026-10-02', 'period_type' => 'weekly']));

        $this->assertSame(['2026-10-02', '2026-10-09', '2026-10-16'], $this->deadlines($t));
    }

    public function test_monthly_deadlines_do_not_overflow(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['start_date' => '2026-01-31', 'period_type' => 'monthly']));

        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31'], $this->deadlines($t));
    }

    public function test_at_least_two_members_are_required(): void
    {
        $t = $this->makeTontine(null, 1);

        $this->actingAs($t->admin)->post(route('tontines.cycles.generate', $t))->assertSessionHasErrors('cycles');
        $this->assertSame(0, $t->roundCycles()->count());
        $this->assertSame('draft', $t->fresh()->status);
    }

    public function test_generating_twice_is_refused(): void
    {
        $t = $this->start($this->makeTontine());

        $this->actingAs($t->admin)->post(route('tontines.cycles.generate', $t))->assertSessionHasErrors('cycles');
        $this->assertSame(3, $t->roundCycles()->count());
    }

    public function test_calendar_can_be_cancelled_while_no_payment_exists(): void
    {
        $t = $this->start($this->makeTontine());

        $this->actingAs($t->admin)->delete(route('tontines.cycles.reset', $t))->assertSessionHasNoErrors();

        $this->assertSame(0, $t->roundCycles()->count());
        $this->assertSame('draft', $t->fresh()->status);
    }

    public function test_calendar_cannot_be_cancelled_once_a_payment_exists(): void
    {
        $t = $this->start($this->makeTontine());
        $this->pay($t, $this->openCycle($t), $t->members()->first());

        $this->actingAs($t->admin)->delete(route('tontines.cycles.reset', $t))->assertSessionHasErrors('cycles');

        $this->assertSame(3, $t->roundCycles()->count());
    }

    public function test_cycle_is_late_only_the_day_after_the_deadline(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['start_date' => '2026-10-02']));
        $cycle = $this->openCycle($t);

        Carbon::setTestNow('2026-10-02 18:00:00');
        $this->assertFalse($cycle->fresh()->isLate(), 'le jour même, pas encore en retard');

        Carbon::setTestNow('2026-10-03 08:00:00');
        $this->assertTrue($cycle->fresh()->isLate());

        Carbon::setTestNow();
    }

    public function test_dashboard_counts_late_cycles(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['start_date' => '2026-10-02']));

        Carbon::setTestNow('2026-10-10');
        $res = $this->actingAs($t->admin)->get('/dashboard')->assertOk();
        $this->assertSame(1, $res->viewData('stats')['late']);
        $this->assertCount(1, $res->viewData('alerts'));

        Carbon::setTestNow();
    }
}
