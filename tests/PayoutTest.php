<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    public function test_no_payout_without_any_contribution(): void
    {
        $t = $this->start($this->makeTontine());

        $this->payout($t, $this->openCycle($t))->assertSessionHasErrors('payout');
        $this->assertSame(0, Payout::count());
    }

    public function test_incomplete_cycle_requires_an_explicit_confirmation(): void
    {
        $t = $this->start($this->makeTontine());
        $cycle = $this->openCycle($t);
        $members = $t->members()->get();
        $this->pay($t, $cycle, $members[0]);
        $this->pay($t, $cycle, $members[1]);

        $this->payout($t, $cycle)->assertSessionHasErrors('payout');
        $this->assertSame(0, Payout::count());
        $this->assertSame('open', $cycle->fresh()->status);

        $this->payout($t, $cycle, true)->assertSessionHasNoErrors();
        $this->assertSame(1, Payout::count());
    }

    public function test_payout_amount_is_computed_server_side_and_closes_the_cycle(): void
    {
        $t = $this->start($this->makeTontine());
        $cycle = $this->openCycle($t);
        $members = $t->members()->get();
        $this->pay($t, $cycle, $members[0]);
        $this->pay($t, $cycle, $members[1]);

        // Un montant envoyé par le client est ignoré
        $this->actingAs($t->admin)->post(
            route('tontines.cycles.payout.store', [$t, $cycle]),
            ['confirm_incomplete' => 1, 'amount' => 999999, 'member_id' => $members[2]->id]
        );

        $payout = Payout::firstOrFail();
        $this->assertEquals(1000, $payout->amount);
        $this->assertSame($cycle->beneficiary_member_id, $payout->member_id);
        $this->assertTrue($payout->confirmed);
        $this->assertSame('closed', $cycle->fresh()->status);
    }

    public function test_the_next_cycle_opens_after_a_payout(): void
    {
        $t = $this->start($this->makeTontine());
        $first = $this->openCycle($t);
        $this->payAll($t, $first);
        $this->payout($t, $first)->assertSessionHasNoErrors();

        $second = $this->openCycle($t);
        $this->assertSame(2, $second->cycle_number);
        $this->assertSame('active', $t->fresh()->status);
    }

    public function test_double_submission_cannot_pay_twice(): void
    {
        $t = $this->start($this->makeTontine());
        $cycle = $this->openCycle($t);
        $this->payAll($t, $cycle);

        $this->payout($t, $cycle)->assertSessionHasNoErrors();
        $this->payout($t, $cycle)->assertSessionHasErrors('payout');

        $this->assertSame(1, Payout::count());
    }

    public function test_payout_is_refused_on_a_pending_cycle(): void
    {
        $t = $this->start($this->makeTontine());
        $pending = $t->roundCycles()->where('status', 'pending')->first();

        $this->payout($t, $pending, true)->assertSessionHasErrors('payout');
        $this->assertSame(0, Payout::count());
    }

    public function test_tontine_is_completed_after_the_last_payout(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 3)));

        $this->assertSame('completed', $t->status);
        $this->assertSame(3, Payout::count());
        $this->assertSame(0, $t->roundCycles()->where('status', '!=', 'closed')->count());
        $this->assertEquals(4500, Payout::sum('amount')); // 3 tours x 3 membres x 500
        $this->assertEquals(4500, Contribution::sum('amount'));
    }

    public function test_every_member_receives_exactly_once_per_round(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 4)));

        $this->assertSame(4, Payout::distinct('member_id')->count('member_id'));
        $this->assertSame(4, Payout::distinct('cycle_id')->count('cycle_id'));
    }
}
