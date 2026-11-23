<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
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

    public function test_a_free_organizer_cannot_invite(): void
    {
        $org = User::factory()->create();
        $t = $this->tontine($org);

        $this->actingAs($org)->post(route('invitations.store', $t), ['phone' => '+509 4700 1111'])
            ->assertRedirect(route('plans'))->assertSessionHas('limit.need', 'member_portal');
        $this->assertSame(0, Invitation::count());
    }

    public function test_an_organizer_creates_an_invitation_and_only_the_hash_is_stored(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);

        $this->actingAs($org)->post(route('invitations.store', $t), [
            'phone' => '+509 4700 1111',
            'contact_permission' => 1,
        ])->assertSessionHas('invite_url')->assertSessionHas('invite_wa')->assertSessionHas('invite_sms');

        $inv = Invitation::firstOrFail();
        $plain = basename((string) session('invite_url')) ?: '';
        $this->assertNotSame($plain, $inv->token_hash);
        $this->assertSame(64, strlen($inv->token_hash));
    }

    public function test_an_invitation_cannot_be_sent_once_the_calendar_is_launched(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org, 'active');

        $this->actingAs($org)->post(route('invitations.store', $t), ['phone' => ''])->assertSessionHasErrors('invite');
        $this->assertSame(0, Invitation::count());
    }

    public function test_a_guest_sees_the_invitation_without_the_member_list(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        $t->members()->create(['name' => 'Moun Sekrè', 'phone' => '+509 4933 3344', 'position' => 1, 'is_active' => true]);
        [, $plain] = Invitation::issue($t, $org, null);

        $this->get(route('invitations.show', $plain))->assertOk()
            ->assertSee('Sòl Test')->assertSee('Organizatè Test')
            ->assertSee('name="name"', false)->assertDontSee(route('login'), false)
            ->assertDontSee('Moun Sekrè')->assertDontSee('4933 3344');
    }

    public function test_an_unknown_or_revoked_invitation_is_not_found(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        [$inv, $plain] = Invitation::issue($t, $org, null);

        $this->get(route('invitations.show', 'lyen-ki-pa-egziste'))->assertNotFound();

        $inv->update(['revoked_at' => now()]);
        $this->get(route('invitations.show', $plain))->assertNotFound();
    }

    public function test_an_expired_invitation_is_not_usable(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        [, $plain] = Invitation::issue($t, $org, null, 0);

        $this->get(route('invitations.show', $plain))->assertNotFound();
    }

    public function test_accepting_creates_a_permanent_membership_linked_to_the_account(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        [$inv, $plain] = Invitation::issue($t, $org, null);
        $invitee = User::factory()->create(['name' => 'Rose Envite']);

        $this->actingAs($invitee)->post(route('invitations.accept', $plain))
            ->assertSessionHasErrors(['name', 'consent']);
        $this->assertSame(0, Member::where('tontine_id', $t->id)->where('user_id', $invitee->id)->count());

        $this->actingAs($invitee)->post(route('invitations.accept', $plain), ['name' => 'Rose Envite', 'consent' => 1])
            ->assertRedirect(route('invitations.joined', Member::where('tontine_id', $t->id)->firstOrFail()->access_token));

        $m = Member::where('user_id', $invitee->id)->firstOrFail();
        $this->assertSame($t->id, $m->tontine_id);
        $this->assertSame('Rose Envite', $m->name);
        $this->assertNotNull($m->consent_at);
        $this->assertNotNull($m->access_token_hash);
        $this->assertNotNull($inv->fresh()->accepted_at);

        $this->get(route('memberships.portal', $m))->assertRedirect(route('portal.home'));
        $this->get(route('portal.home'))->assertOk()->assertSee('Sòl Test');
    }

    public function test_an_invitation_can_only_be_accepted_once(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        [, $plain] = Invitation::issue($t, $org, null);
        $invitee = User::factory()->create();

        $payload = ['name' => 'Rose Envite', 'consent' => 1];
        $this->actingAs($invitee)->post(route('invitations.accept', $plain), $payload);
        $this->actingAs($invitee)->post(route('invitations.accept', $plain), $payload)->assertSessionHasErrors('invite');

        $this->assertSame(1, Member::where('user_id', $invitee->id)->count());
    }

    public function test_a_member_can_accept_a_second_invitation_without_a_duplicate_membership(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        [$firstInvitation, $firstToken] = Invitation::issue($t, $org, null);
        [$secondInvitation, $secondToken] = Invitation::issue($t, $org, null);
        $invitee = User::factory()->create();

        $payload = ['name' => 'Rose Envite', 'consent' => 1];
        $this->actingAs($invitee)->post(route('invitations.accept', $firstToken), $payload);
        $this->actingAs($invitee)->post(route('invitations.accept', $secondToken), $payload)
            ->assertRedirect(route('invitations.joined', Member::where('tontine_id', $t->id)->firstOrFail()->access_token));

        $this->assertSame(1, Member::where('tontine_id', $t->id)->where('user_id', $invitee->id)->count());
        $this->assertNotNull($firstInvitation->fresh()->accepted_at);
        $this->assertNotNull($secondInvitation->fresh()->accepted_at);
    }

    public function test_a_guest_can_join_with_their_name_and_receive_portal_access(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        [$invitation, $plain] = Invitation::issue($t, $org, '+509 4700 1111');

        $this->post(route('invitations.accept', $plain), ['name' => 'Rose Jean', 'consent' => 1])
            ->assertRedirect();

        $member = Member::where('tontine_id', $t->id)->firstOrFail();
        $this->assertNull($member->user_id);
        $this->assertSame('Rose Jean', $member->name);
        $this->assertSame('+509 4700 1111', $member->phone);
        $this->assertNotNull($member->access_token_hash);
        $this->assertNotNull($member->consent_at);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->get(route('invitations.joined', $member->access_token))
            ->assertOk()->assertSee('Lyen pèsonèl pòtay manm ou an');
        $this->get(route('portal.enter', $member->access_token))->assertRedirect(route('portal.home'));
        $this->get(route('portal.home'))->assertOk()->assertSee('Sòl Test');
    }

    public function test_the_memberships_page_lists_only_the_users_own_tontines(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        $me = User::factory()->create();
        $t->members()->create(['name' => 'Autre', 'position' => 1, 'is_active' => true]);
        $m = new Member;
        $m->forceFill(['tontine_id' => $t->id, 'user_id' => $me->id, 'name' => 'Mwen', 'position' => 2, 'is_active' => true])->save();

        $this->actingAs($me)->get(route('memberships.index'))->assertOk()->assertSee('Sòl Test');
    }

    public function test_a_pending_account_invitation_prevents_the_tontine_from_starting(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        $t->members()->create(['name' => 'Autre membre', 'position' => 1, 'is_active' => true]);
        Invitation::issue($t, $org, '+509 4700 1111');

        $this->actingAs($org)->post(route('tontines.cycles.generate', $t))
            ->assertSessionHasErrors('cycles');
        $this->assertSame('draft', $t->fresh()->status);
    }

    public function test_only_the_linked_account_can_reenter_its_portal_and_not_after_closure(): void
    {
        $org = $this->proOrganizer();
        $t = $this->tontine($org);
        $invitee = User::factory()->create();
        [$invitation, $token] = Invitation::issue($t, $org, null);

        $this->actingAs($invitee)->post(route('invitations.accept', $token), ['name' => 'Rose Envite', 'consent' => 1]);
        $member = Member::where('tontine_id', $t->id)->where('user_id', $invitee->id)->firstOrFail();

        $this->actingAs(User::factory()->create())->get(route('memberships.portal', $member))->assertNotFound();

        $this->actingAs($invitee)->get(route('memberships.portal', $member))->assertRedirect(route('portal.home'));
        $t->update(['status' => 'completed']);
        $this->actingAs($invitee)->get(route('memberships.portal', $member))->assertNotFound();
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }
}
