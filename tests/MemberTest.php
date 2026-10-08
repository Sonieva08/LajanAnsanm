<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    public function test_new_members_get_the_next_position(): void
    {
        $t = $this->makeTontine(null, 2);

        $this->actingAs($t->admin)->post(route('tontines.members.store', $t), ['name' => 'Nouveau', 'phone' => '+509 3333 4444'])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $t->members()->where('name', 'Nouveau')->first()->position);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $t = $this->makeTontine(null, 2);

        $this->actingAs($t->admin)->post(route('tontines.members.store', $t), ['name' => 'X', 'phone' => 'abc'])
            ->assertSessionHasErrors('phone');
    }

    public function test_move_up_and_down_swaps_positions(): void
    {
        $t = $this->makeTontine(null, 3);
        $second = $t->members()->where('position', 2)->first();

        $this->actingAs($t->admin)->post(route('tontines.members.move', [$t, $second, 'up']));
        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $t->members()->where('name', 'Membre 1')->first()->position);

        $this->actingAs($t->admin)->post(route('tontines.members.move', [$t, $second->fresh(), 'down']));
        $this->assertSame(2, $second->fresh()->position);
    }

    public function test_shuffle_keeps_positions_unique_and_complete(): void
    {
        $t = $this->makeTontine(null, 6);

        $this->actingAs($t->admin)->post(route('tontines.members.shuffle', $t))->assertSessionHasNoErrors();

        $this->assertSame([1, 2, 3, 4, 5, 6], $t->members()->pluck('position')->sort()->values()->all());
    }

    public function test_deleting_a_member_renumbers_without_gaps(): void
    {
        $t = $this->makeTontine(null, 4);
        $middle = $t->members()->where('position', 2)->first();

        $this->actingAs($t->admin)->delete(route('tontines.members.destroy', [$t, $middle]));

        $this->assertSame([1, 2, 3], $t->members()->pluck('position')->all());
    }

    public function test_member_list_is_locked_once_the_calendar_exists(): void
    {
        $t = $this->start($this->makeTontine());
        $m = $t->members()->first();

        $this->actingAs($t->admin)->post(route('tontines.members.store', $t), ['name' => 'Tard'])
            ->assertSessionHasErrors('locked');
        $this->actingAs($t->admin)->delete(route('tontines.members.destroy', [$t, $m]))
            ->assertSessionHasErrors('locked');
        $this->actingAs($t->admin)->post(route('tontines.members.shuffle', $t))
            ->assertSessionHasErrors('locked');
        $this->actingAs($t->admin)->post(route('tontines.members.move', [$t, $m, 'down']))
            ->assertSessionHasErrors('locked');

        $this->assertSame(3, $t->members()->count());
    }

    public function test_a_member_cannot_be_reached_through_another_tontine(): void
    {
        $a = $this->makeTontine();
        $b = $this->makeTontine();
        $memberOfB = $b->members()->first();

        $this->actingAs($a->admin)->put(route('tontines.members.update', [$a, $memberOfB]), ['name' => 'Hack'])
            ->assertNotFound();

        $this->assertNotSame('Hack', $memberOfB->fresh()->name);
    }

    public function test_member_with_history_is_deactivated_not_deleted(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 2)));
        $this->actingAs($t->admin)->post(route('tontines.cycles.restart', $t), ['start_date' => today()->format('Y-m-d')]);
        $t = $t->fresh()->load('admin');

        $veteran = $t->members()->where('position', 1)->first();
        $contribs = $veteran->contributions()->count();
        $this->assertGreaterThan(0, $contribs);

        $this->actingAs($t->admin)->delete(route('tontines.members.destroy', [$t, $veteran]))->assertSessionHasNoErrors();

        $veteran = Member::find($veteran->id);
        $this->assertNotNull($veteran, 'le membre ne doit pas être supprimé');
        $this->assertFalse($veteran->is_active);
        $this->assertNull($veteran->position);
        $this->assertSame($contribs, $veteran->contributions()->count(), "l'historique doit rester");

        // Réactivation possible tant que le calendrier n'est pas lancé
        $this->actingAs($t->admin)->post(route('tontines.members.reactivate', [$t, $veteran]));
        $this->assertTrue($veteran->fresh()->is_active);
    }

    public function test_member_without_history_is_really_deleted(): void
    {
        $t = $this->makeTontine(null, 3);
        $m = $t->members()->first();

        $this->actingAs($t->admin)->delete(route('tontines.members.destroy', [$t, $m]));

        $this->assertNull(Member::find($m->id));
    }
}
