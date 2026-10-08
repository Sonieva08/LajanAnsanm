<?php

namespace Tests\Feature;

use App\Models\Payout;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class OrganizerFeeTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function payload(array $over = []): array
    {
        return array_merge([
            'name' => 'Sòl Famille',
            'amount_per_period' => 500,
            'organizer_fee_percent' => 10,
            'period_type' => 'weekly',
            'start_date' => today()->format('Y-m-d'),
        ], $over);
    }

    public function test_the_fee_is_saved_at_creation(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)->post(route('tontines.store'), $this->payload(['organizer_fee_percent' => 7.5]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(7.5, Tontine::firstOrFail()->organizer_fee_percent);
    }

    public function test_the_fee_defaults_to_zero_when_left_empty(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)->post(route('tontines.store'), $this->payload(['organizer_fee_percent' => '']))
            ->assertSessionHasNoErrors();

        $t = Tontine::firstOrFail();
        $this->assertEquals(0, $t->fresh()->organizer_fee_percent);
        $this->assertFalse($t->fresh()->hasFee());
    }

    public function test_invalid_fees_are_rejected(): void
    {
        $user = $this->premiumUser();

        foreach ([31, 100, -1, 'abc', 5.555] as $bad) {
            $this->actingAs($user)->post(route('tontines.store'), $this->payload(['organizer_fee_percent' => $bad]))
                ->assertSessionHasErrors('organizer_fee_percent');
        }

        $this->assertSame(0, Tontine::count());
    }

    public function test_the_payout_deducts_the_fee_and_records_a_snapshot(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['organizer_fee_percent' => 10]));
        $cycle = $this->openCycle($t);
        $this->payAll($t, $cycle);

        $this->payout($t, $cycle)->assertSessionHasNoErrors();

        $p = Payout::firstOrFail();
        $this->assertEquals(1500, $p->amount);       // total collecté
        $this->assertEquals(10, $p->fee_percent);
        $this->assertEquals(150, $p->fee_amount);    // commission
        $this->assertEquals(1350, $p->netAmount());  // remis au bénéficiaire
    }

    public function test_without_a_fee_the_beneficiary_gets_everything(): void
    {
        $t = $this->start($this->makeTontine(null, 3));
        $cycle = $this->openCycle($t);
        $this->payAll($t, $cycle);
        $this->payout($t, $cycle);

        $p = Payout::firstOrFail();
        $this->assertEquals(0, $p->fee_amount);
        $this->assertEquals(1500, $p->netAmount());
    }

    public function test_the_fee_applies_to_what_was_really_collected(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['organizer_fee_percent' => 7.5]));
        $cycle = $this->openCycle($t);
        $members = $t->members()->get();
        $this->pay($t, $cycle, $members[0]);
        $this->pay($t, $cycle, $members[1]);
        $this->payout($t, $cycle, true);

        $p = Payout::firstOrFail();
        $this->assertEquals(1000, $p->amount);
        $this->assertEquals(75, $p->fee_amount);
        $this->assertEquals(925, $p->netAmount());
    }

    public function test_the_fee_is_rounded_to_the_cent(): void
    {
        $t = $this->makeTontine(null, 3, ['amount_per_period' => 333, 'organizer_fee_percent' => 3.33]);

        $this->assertSame(33.27, $t->feeFor(999.0)); // 33,2667 arrondi
        $this->assertSame(0.0, $this->makeTontine(null, 3)->feeFor(999.0));
    }

    public function test_a_client_cannot_inject_a_fee_through_the_payout_request(): void
    {
        $t = $this->start($this->makeTontine(null, 3));
        $cycle = $this->openCycle($t);
        $this->payAll($t, $cycle);

        $this->actingAs($t->admin)->post(route('tontines.cycles.payout.store', [$t, $cycle]),
            ['fee_amount' => 1400, 'fee_percent' => 90, 'amount' => 1]);

        $p = Payout::firstOrFail();
        $this->assertEquals(0, $p->fee_amount);
        $this->assertEquals(1500, $p->amount);
    }

    public function test_rules_are_frozen_once_the_calendar_exists(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['organizer_fee_percent' => 10]));

        $this->actingAs($t->admin)->put(route('tontines.update', $t), $this->payload([
            'name' => 'Nouveau nom', 'amount_per_period' => 9999, 'organizer_fee_percent' => 0,
            'period_type' => 'monthly', 'start_date' => today()->addYear()->format('Y-m-d'),
        ]))->assertSessionHasNoErrors();

        $t = $t->fresh();
        $this->assertSame('Nouveau nom', $t->name);          // le nom reste modifiable
        $this->assertEquals(500, $t->amount_per_period);     // le reste est figé
        $this->assertEquals(10, $t->organizer_fee_percent);
        $this->assertSame('weekly', $t->period_type);
    }

    public function test_rules_can_change_while_in_draft(): void
    {
        $t = $this->makeTontine(null, 3);

        $this->actingAs($t->admin)->put(route('tontines.update', $t), $this->payload([
            'amount_per_period' => 800, 'organizer_fee_percent' => 12,
        ]))->assertSessionHasNoErrors();

        $t = $t->fresh();
        $this->assertEquals(800, $t->amount_per_period);
        $this->assertEquals(12, $t->organizer_fee_percent);
    }

    public function test_past_payouts_keep_their_fee_when_the_rule_changes_in_a_later_round(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 3, ['organizer_fee_percent' => 10])));
        $this->assertEquals(450, Payout::sum('fee_amount')); // 3 tours x 150

        $this->actingAs($t->admin)->post(route('tontines.cycles.restart', $t), ['start_date' => today()->format('Y-m-d')]);
        $this->actingAs($t->admin)->put(route('tontines.update', $t->fresh()), $this->payload(['organizer_fee_percent' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(0, $t->fresh()->organizer_fee_percent);
        $this->assertEquals(450, Payout::sum('fee_amount'), "l'historique ne change pas");
        $this->assertSame(3, Payout::where('fee_percent', 10)->count());
    }

    public function test_reports_include_the_fees(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 3, ['organizer_fee_percent' => 10])));

        $res = $this->actingAs($t->admin)->get(route('tontines.reports.round', [$t, 1]))->assertOk();
        $totals = $res->viewData('totals');
        $this->assertEquals(4500, $totals['collected']);
        $this->assertEquals(450, $totals['fees']);
        $this->assertEquals(4050, $totals['paid_out']); // net remis aux bénéficiaires

        $cycle = $t->roundCycles()->first();
        $this->actingAs($t->admin)->get(route('tontines.reports.tour', [$t, $cycle]))->assertOk();

        $csv = $this->actingAs($t->admin)
            ->get(route('tontines.reports.tour', [$t, $cycle, 'format' => 'csv']))->streamedContent();
        $this->assertMatchesRegularExpression('/,150\r?\n/', $csv);
        $this->assertMatchesRegularExpression('/,1350\r?\n/', $csv);
    }

    public function test_pages_render_with_a_fee(): void
    {
        $t = $this->start($this->makeTontine(null, 3, ['organizer_fee_percent' => 10]));
        $this->payAll($t, $this->openCycle($t));

        foreach (['tontines.show', 'tontines.edit', 'tontines.payouts.index', 'tontines.cycles.index', 'tontines.reports.index'] as $name) {
            $this->actingAs($t->admin)->get(route($name, $t))->assertOk();
        }

        $draft = $this->makeTontine(null, 3, ['organizer_fee_percent' => 10]);
        foreach (['tontines.cycles.index', 'tontines.edit', 'tontines.show'] as $name) {
            $this->actingAs($draft->admin)->get(route($name, $draft))->assertOk();
        }
        $this->actingAs($draft->admin)->get(route('tontines.create'))->assertOk();
    }
}
