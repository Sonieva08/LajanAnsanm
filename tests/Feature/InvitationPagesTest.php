<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationPagesTest extends TestCase
{
    use RefreshDatabase;

    private function proOrganizer(): User
    {
        $u = User::factory()->create(['name' => 'Organizatè Test']);
        $plan = Plan::updateOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true, 'price_monthly' => 2500,
            'sort_order' => 3, 'max_active_tontines' => 10, 'max_members_per_tontine' => 50, 'features' => ['member_portal']]);
        Subscription::create(['user_id' => $u->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(),
            'ends_at' => now()->addMonth(), 'amount' => 0, 'source' => 'owner_grant']);

        return $u;
    }

    private function tontine(User $admin, string $status = 'draft'): Tontine
    {
        return Tontine::create(['admin_id' => $admin->id, 'name' => 'Sòl Test', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => $status, 'member_portal_enabled' => true]);
    }

    public function test_the_organizer_page_is_only_for_the_owner(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);

        $this->actingAs($org)->get(route('invitations.create', $t))->assertOk()->assertSee('Kreye envitasyon');
        $this->actingAs(User::factory()->create())->get(route('invitations.create', $t))->assertForbidden();
    }

    public function test_the_organizer_page_locks_once_the_calendar_is_launched(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org, 'active');

        $this->actingAs($org)->get(route('invitations.create', $t))->assertOk()
            ->assertSee('Kalandriye a deja lanse')->assertDontSee('Kreye envitasyon');
    }

    public function test_creating_a_link_returns_to_the_page_with_the_link_once(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);

        $this->actingAs($org)->post(route('invitations.store', $t), [
            'phone' => '+509 4700 1111',
            'contact_permission' => 1,
        ])
            ->assertRedirect(route('invitations.create', $t))
            ->assertSessionHas('invite_url')
            ->assertSessionHas('invite_wa')
            ->assertSessionHas('invite_sms');

        $this->assertSame(1, Invitation::count());
    }

    public function test_the_accept_page_asks_for_a_name_without_requiring_an_account(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        [, $plain] = Invitation::issue($t, $org, null);

        $this->get(route('invitations.show', $plain))->assertOk()
            ->assertSee('name="name"', false)->assertDontSee(route('login'), false)
            ->assertDontSee(route('register'), false)->assertSee('Organizatè Test')->assertSee('Sòl Test');

        $this->actingAs(User::factory()->create())->get(route('invitations.show', $plain))->assertOk()
            ->assertSee('Aksepte envitasyon an')
            ->assertSee('name="name"', false)->assertSee('name="consent"', false);
    }

    public function test_an_expired_link_shows_the_friendly_message(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        [, $plain] = Invitation::issue($t, $org, null, 0);

        $this->get(route('invitations.show', $plain))->assertNotFound()->assertSee('Envitasyon sa a pa valab ankò');
    }
}
