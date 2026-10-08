<?php

namespace Tests\Feature;

use App\Models\Contribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class ContributionTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    public function test_a_payment_is_recorded_with_the_tontine_amount(): void
    {
        $t = $this->start($this->makeTontine());
        $cycle = $this->openCycle($t);

        // Même si le client tente d'imposer un autre montant, il est ignoré
        $this->pay($t, $cycle, $t->members()->first(), ['amount' => 1])->assertSessionHasNoErrors();

        $c = Contribution::firstOrFail();
        $this->assertEquals(500, $c->amount);
        $this->assertSame('cash', $c->payment_method);
    }

    public function test_a_member_cannot_pay_twice_for_the_same_cycle(): void
    {
        $t = $this->start($this->makeTontine());
        $cycle = $this->openCycle($t);
        $m = $t->members()->first();

        $this->pay($t, $cycle, $m)->assertSessionHasNoErrors();
        $this->pay($t, $cycle, $m)->assertSessionHasErrors('contribution');

        $this->assertSame(1, Contribution::count());
    }

    public function test_payment_is_refused_on_a_pending_cycle(): void
    {
        $t = $this->start($this->makeTontine());
        $pending = $t->roundCycles()->where('status', 'pending')->first();

        $this->pay($t, $pending, $t->members()->first())->assertSessionHasErrors('contribution');
        $this->assertSame(0, Contribution::count());
    }

    public function test_payment_is_refused_for_a_member_of_another_tontine(): void
    {
        $t = $this->start($this->makeTontine());
        $stranger = $this->makeTontine()->members()->first();

        $this->pay($t, $this->openCycle($t), $stranger)->assertSessionHasErrors('member_id');
        $this->assertSame(0, Contribution::count());
    }

    public function test_future_dates_and_unknown_methods_are_rejected(): void
    {
        $t = $this->start($this->makeTontine());
        $cycle = $this->openCycle($t);
        $m = $t->members()->first();

        $this->pay($t, $cycle, $m, ['paid_at' => today()->addDay()->format('Y-m-d')])->assertSessionHasErrors('paid_at');
        $this->pay($t, $cycle, $m, ['payment_method' => 'bitcoin'])->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Contribution::count());
    }

    public function test_a_payment_can_be_cancelled_while_the_cycle_is_open(): void
    {
        $t = $this->start($this->makeTontine());
        $cycle = $this->openCycle($t);
        $this->pay($t, $cycle, $t->members()->first());
        $c = Contribution::firstOrFail();

        $this->actingAs($t->admin)->delete(route('tontines.cycles.contributions.destroy', [$t, $cycle, $c]))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Contribution::count());
    }

    public function test_a_payment_cannot_be_cancelled_after_the_cycle_is_closed(): void
    {
        $t = $this->start($this->makeTontine(null, 2));
        $cycle = $this->openCycle($t);
        $this->payAll($t, $cycle);
        $this->payout($t, $cycle);
        $c = Contribution::where('cycle_id', $cycle->id)->first();

        $this->actingAs($t->admin)->delete(route('tontines.cycles.contributions.destroy', [$t, $cycle, $c]))
            ->assertSessionHasErrors('contribution');

        $this->assertNotNull(Contribution::find($c->id));
    }

    public function test_contributions_page_redirects_until_the_calendar_exists(): void
    {
        $t = $this->makeTontine();

        $this->actingAs($t->admin)->get(route('tontines.contributions.index', $t))
            ->assertRedirect(route('tontines.cycles.index', $t));
    }

    public function test_whatsapp_reminder_link_is_built_with_the_haiti_prefix(): void
    {
        $t = $this->start($this->makeTontine(null, 3));
        $t->members()->first()->update(['phone' => '3700 1234']); // 8 chiffres sans indicatif

        $this->actingAs($t->admin)->get(route('tontines.contributions.index', $t))
            ->assertOk()
            ->assertSee('https://wa.me/50937001234', false);
    }
}
