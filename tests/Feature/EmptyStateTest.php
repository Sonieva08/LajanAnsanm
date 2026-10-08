<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class EmptyStateTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    public function test_a_new_user_sees_friendly_empty_states(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('data-empty-state', false);
        $this->actingAs($user)->get(route('tontines.index'))->assertOk()->assertSee('data-empty-state', false);
    }

    public function test_an_empty_tontine_explains_what_to_do_next(): void
    {
        $t = $this->makeTontine(null, 0);

        foreach (['tontines.members.index', 'tontines.reports.index', 'tontines.payouts.index'] as $route) {
            $this->actingAs($t->admin)->get(route($route, $t))->assertOk()->assertSee('data-empty-state', false);
        }
    }

    public function test_a_filled_list_does_not_show_the_empty_state(): void
    {
        $t = $this->start($this->makeTontine());

        $this->actingAs($t->admin)->get(route('tontines.members.index', $t))->assertOk()->assertDontSee('data-empty-state', false);
        $this->actingAs($t->admin)->get(route('tontines.index'))->assertOk()->assertDontSee('data-empty-state', false);
    }

    public function test_the_console_shows_empty_states_when_there_is_no_data(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('admin.users'))->assertOk()->assertSee('data-empty-state', false);
        $this->actingAs($owner)->get(route('admin.activity', ['action' => 'payout_made']))->assertOk()->assertSee('data-empty-state', false);
    }
}
