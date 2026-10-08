<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Cycle;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class RestartTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function restart($t, array $data = [])
    {
        return $this->actingAs($t->admin)->post(
            route('tontines.cycles.restart', $t),
            array_merge(['start_date' => today()->addWeek()->format('Y-m-d')], $data)
        );
    }

    public function test_restart_is_refused_while_the_tontine_is_not_completed(): void
    {
        $t = $this->start($this->makeTontine());

        $this->restart($t)->assertSessionHasErrors('cycles');

        $t = $t->fresh();
        $this->assertSame(1, $t->current_round);
        $this->assertSame('active', $t->status);
    }

    public function test_restart_opens_a_new_round_in_draft_and_unlocks_members(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 3)));

        $this->restart($t)->assertRedirect(route('tontines.members.index', $t));

        $t = $t->fresh()->load('admin');
        $this->assertSame(2, $t->current_round);
        $this->assertSame('draft', $t->status);
        $this->assertSame(today()->addWeek()->format('Y-m-d'), $t->start_date->format('Y-m-d'));
        $this->assertFalse($t->isLocked());

        // On peut ajouter un nouveau membre avant de relancer
        $this->actingAs($t->admin)->post(route('tontines.members.store', $t), ['name' => 'Nouvelle recrue'])
            ->assertSessionHasNoErrors();
        $this->assertSame(4, $t->members()->count());
    }

    public function test_history_is_preserved_and_numbering_restarts_in_the_new_round(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 3)));
        $contribs = Contribution::count();
        $payouts = Payout::count();

        $this->restart($t);
        $t = $this->start($t->fresh()->load('admin'));

        $this->assertSame($contribs, Contribution::count());
        $this->assertSame($payouts, Payout::count());
        $this->assertSame(6, Cycle::where('tontine_id', $t->id)->count());

        $round2 = $t->roundCycles()->get();
        $this->assertSame([1, 2, 3], $round2->pluck('cycle_number')->all());
        $this->assertSame([2, 2, 2], $round2->pluck('round')->all());
        $this->assertSame(0, $t->roundContributions()->count(), 'le nouveau kòl démarre à zéro');
        $this->assertSame(3, Cycle::where('round', 1)->where('status', 'closed')->count());
    }

    public function test_a_new_member_joins_the_new_calendar(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 3)));
        $this->restart($t);
        $t = $t->fresh()->load('admin');
        $this->actingAs($t->admin)->post(route('tontines.members.store', $t), ['name' => 'Nouvelle recrue']);

        $t = $this->start($t);

        $this->assertSame(4, $t->roundCycles()->count());
        $this->assertSame('Nouvelle recrue', $t->roundCycles()->get()->last()->beneficiary->name);
    }

    public function test_cancelling_the_new_calendar_keeps_the_previous_round(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 3)));
        $this->restart($t);
        $t = $this->start($t->fresh()->load('admin'));

        $this->actingAs($t->admin)->delete(route('tontines.cycles.reset', $t))->assertSessionHasNoErrors();

        $this->assertSame(0, $t->roundCycles()->count());
        $this->assertSame(3, Cycle::where('tontine_id', $t->id)->where('round', 1)->count());
    }

    public function test_optional_shuffle_keeps_positions_valid(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 5)));

        $this->restart($t, ['shuffle' => 1])->assertSessionHasNoErrors();

        $this->assertSame([1, 2, 3, 4, 5], $t->members()->pluck('position')->sort()->values()->all());
    }

    public function test_past_start_date_is_rejected(): void
    {
        $t = $this->complete($this->start($this->makeTontine()));

        $this->restart($t, ['start_date' => today()->subDay()->format('Y-m-d')])->assertSessionHasErrors('start_date');

        $this->assertSame(1, $t->fresh()->current_round);
    }

    public function test_double_click_cannot_start_two_rounds(): void
    {
        $t = $this->complete($this->start($this->makeTontine()));

        $this->restart($t);
        $this->restart($t)->assertSessionHasErrors('cycles');

        $this->assertSame(2, $t->fresh()->current_round);
    }

    public function test_pages_show_the_restart_option_when_completed(): void
    {
        $t = $this->complete($this->start($this->makeTontine()));
        $url = route('tontines.cycles.restart', $t);

        $this->actingAs($t->admin)->get(route('tontines.cycles.index', $t))->assertOk()->assertSee($url, false);
        $this->actingAs($t->admin)->get(route('tontines.show', $t))->assertOk()
            ->assertSee(route('tontines.cycles.index', $t), false);
    }

    public function test_a_completed_round_cannot_be_cancelled(): void
    {
        $t = $this->complete($this->start($this->makeTontine()));

        $this->actingAs($t->admin)->delete(route('tontines.cycles.reset', $t))->assertSessionHasErrors('cycles');

        $this->assertSame(3, $t->roundCycles()->count());
    }
}
