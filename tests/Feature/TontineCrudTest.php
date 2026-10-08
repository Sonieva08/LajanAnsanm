<?php

namespace Tests\Feature;

use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class TontineCrudTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function payload(array $over = []): array
    {
        return array_merge([
            'name' => 'Sòl Famille',
            'amount_per_period' => 1000,
            'period_type' => 'monthly',
            'start_date' => today()->format('Y-m-d'),
        ], $over);
    }

    public function test_a_user_can_create_a_tontine_owned_by_them(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tontines.store'), $this->payload())->assertRedirect();

        $t = Tontine::firstOrFail();
        $this->assertSame($user->id, $t->admin_id);
        $this->assertSame('draft', $t->status);
        $this->assertSame(1, $t->current_round);
    }

    public function test_the_admin_id_and_status_cannot_be_forced_from_the_form(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('tontines.store'),
            $this->payload(['admin_id' => $other->id, 'status' => 'completed', 'current_round' => 9]));

        $t = Tontine::firstOrFail();
        $this->assertSame($user->id, $t->admin_id);
        $this->assertSame('draft', $t->status);
        $this->assertSame(1, $t->current_round);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tontines.store'), $this->payload(['amount_per_period' => 0]))
            ->assertSessionHasErrors('amount_per_period');
        $this->actingAs($user)->post(route('tontines.store'), $this->payload(['period_type' => 'daily']))
            ->assertSessionHasErrors('period_type');
        $this->actingAs($user)->post(route('tontines.store'), $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Tontine::count());
    }

    public function test_owner_can_update_and_delete(): void
    {
        $t = $this->makeTontine();

        $this->actingAs($t->admin)->put(route('tontines.update', $t), $this->payload(['name' => 'Nouveau nom']))
            ->assertRedirect();
        $this->assertSame('Nouveau nom', $t->fresh()->name);

        $this->actingAs($t->admin)->delete(route('tontines.destroy', $t))->assertRedirect(route('tontines.index'));
        $this->assertSame(0, Tontine::count());
    }

    public function test_another_user_cannot_update_or_delete_even_with_valid_data(): void
    {
        $t = $this->makeTontine();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->put(route('tontines.update', $t), $this->payload(['name' => 'Piratée']))
            ->assertForbidden();
        $this->actingAs($intruder)->delete(route('tontines.destroy', $t))->assertForbidden();

        $this->assertSame('Sòl Test', $t->fresh()->name);
    }
}
