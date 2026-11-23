<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Tontine;
use App\Notifications\TontineMemberInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class MemberInvitationTest extends TestCase
{
    use BuildsTontines, RefreshDatabase;

    private function tontineWithPortal(): Tontine
    {
        $tontine = $this->makeTontine();
        $tontine->update(['member_portal_enabled' => true]);

        return $tontine->fresh()->load('admin');
    }

    public function test_organizer_creates_a_pending_invitation_and_emails_it(): void
    {
        Notification::fake();
        $tontine = $this->tontineWithPortal();

        $this->actingAs($tontine->admin)->post(route('tontines.members.invite', $tontine), [
            'name' => 'Nouveau membre',
            'phone' => '+509 4700 1234',
            'email' => 'invite@example.com',
            'contact_permission' => 1,
        ])->assertSessionHas('status', 'member-invitation-created')
            ->assertSessionHas('invitation-email-status', 'sent');

        $member = $tontine->members()->where('email', 'invite@example.com')->firstOrFail();
        $this->assertFalse($member->is_active);
        $this->assertNull($member->position);
        $this->assertNotNull($member->invited_at);
        $this->assertNull($member->invitation_accepted_at);
        $this->assertNotNull($member->access_token);
        $this->assertNotNull($member->invitationUrl());
        $this->assertSame(Member::hashToken($member->access_token), $member->access_token_hash);

        Notification::assertSentOnDemand(TontineMemberInvitation::class, function ($notification, array $channels, $notifiable) {
            return $channels === ['mail'] && $notifiable->routes['mail'] === 'invite@example.com';
        });
    }

    public function test_invitee_must_accept_and_give_consent_before_receiving_portal_access(): void
    {
        $tontine = $this->tontineWithPortal();
        $member = $this->invite($tontine);
        $url = $member->invitationUrl();

        $this->get($url)->assertOk()
            ->assertSee($tontine->name)
            ->assertSee('name="consent"', false);
        $this->assertFalse($member->fresh()->is_active);

        $this->post(route('member.invitation.accept', $member->access_token))
            ->assertSessionHasErrors('consent');
        $this->assertFalse($member->fresh()->is_active);

        $this->post(route('member.invitation.accept', $member->access_token), ['consent' => 1])
            ->assertRedirect(route('portal.enter', $member->access_token));

        $member->refresh();
        $this->assertTrue($member->is_active);
        $this->assertSame(4, $member->position);
        $this->assertNotNull($member->consent_at);
        $this->assertNotNull($member->invitation_accepted_at);
        $this->get(route('portal.enter', $member->access_token))->assertRedirect(route('portal.home'));
        $this->get(route('portal.home'))->assertOk()->assertSee($tontine->name);
    }

    public function test_the_organizer_can_share_pending_invitation_by_whatsapp_sms_or_copy_link(): void
    {
        $tontine = $this->tontineWithPortal();
        $member = $this->invite($tontine);
        $member->update(['email' => 'invite@example.com']);

        $this->actingAs($tontine->admin)->get(route('tontines.members.index', $tontine))
            ->assertOk()
            ->assertSee('https://wa.me/50947001234', false)
            ->assertSee('sms:', false)
            ->assertSee('mailto:invite@example.com', false)
            ->assertSee($member->invitationUrl(), false);
    }

    public function test_tontine_cannot_start_while_an_invitation_is_pending(): void
    {
        $tontine = $this->tontineWithPortal();
        $this->invite($tontine);

        $this->actingAs($tontine->admin)
            ->post(route('tontines.cycles.generate', $tontine))
            ->assertSessionHasErrors('cycles');

        $this->assertSame('draft', $tontine->fresh()->status);
        $this->assertSame(0, $tontine->cycles()->count());
    }

    public function test_invited_member_portal_stops_when_the_tontine_is_completed(): void
    {
        $tontine = $this->tontineWithPortal();
        $member = $this->invite($tontine);
        $this->post(route('member.invitation.accept', $member->access_token), ['consent' => 1]);
        $this->get(route('portal.enter', $member->access_token))->assertRedirect(route('portal.home'));
        $this->get(route('portal.home'))->assertOk();

        $tontine->update(['status' => 'completed']);

        $this->get(route('portal.home'))->assertForbidden();
        $this->get(route('portal.enter', $member->access_token))->assertNotFound();
    }

    public function test_inviting_requires_a_contact_method_and_contact_permission(): void
    {
        $tontine = $this->tontineWithPortal();

        $this->actingAs($tontine->admin)->post(route('tontines.members.invite', $tontine), [
            'name' => 'Invité',
            'contact_permission' => 1,
        ])->assertSessionHasErrors(['phone', 'email']);

        $this->actingAs($tontine->admin)->post(route('tontines.members.invite', $tontine), [
            'name' => 'Invité',
            'phone' => '------',
            'contact_permission' => 1,
        ])->assertSessionHasErrors('phone');

        $this->actingAs($tontine->admin)->post(route('tontines.members.invite', $tontine), [
            'name' => 'Invité',
            'phone' => '+509 4700 5555',
        ])->assertSessionHasErrors('contact_permission');

        $this->assertSame(3, $tontine->members()->count());
    }

    private function invite(Tontine $tontine): Member
    {
        $this->actingAs($tontine->admin)->post(route('tontines.members.invite', $tontine), [
            'name' => 'Nouveau membre',
            'phone' => '+509 4700 1234',
            'contact_permission' => 1,
        ])->assertSessionHasNoErrors();

        return $tontine->members()->where('name', 'Nouveau membre')->firstOrFail();
    }
}
