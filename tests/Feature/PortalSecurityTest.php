<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function creator(bool $pro = false): User
    {
        $u = User::factory()->create();

        if ($pro) {
            $plan = Plan::updateOrCreate(['slug' => 'pro'], [
                'name' => 'Pro', 'is_active' => true, 'price_monthly' => 2500, 'sort_order' => 3,
                'max_active_tontines' => 10, 'max_members_per_tontine' => 50, 'features' => ['member_portal'],
            ]);
        } else {
            $plan = Plan::where('is_free', true)->firstOrFail();
        }
        Subscription::create(['user_id' => $u->id, 'plan_id' => $plan->id, 'status' => 'active',
            'starts_at' => now(), 'ends_at' => now()->addMonth(), 'amount' => 0, 'source' => 'owner_grant']);

        return $u;
    }

    private function tontine(User $u, bool $portal = true, bool $legacy = false): Tontine
    {
        $t = Tontine::create(['admin_id' => $u->id, 'name' => 'Sòl', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => 'active']);
        $t->forceFill(['member_portal_enabled' => $portal, 'is_legacy' => $legacy])->save();

        return $t;
    }

    private function member(Tontine $t): Member
    {
        return $t->members()->create(['name' => 'Marie', 'position' => 1, 'is_active' => true]);
    }

    public function test_a_free_creator_cannot_turn_the_member_space_on(): void
    {
        $u = $this->creator();
        $t = $this->tontine($u, false);

        $this->actingAs($u)->put(route('tontines.portal.update', $t), ['member_portal_enabled' => 1])
            ->assertRedirect(route('plans'))->assertSessionHas('limit.need', 'member_portal');

        $this->assertFalse((bool) $t->fresh()->member_portal_enabled);
    }

    public function test_a_premium_creator_can_turn_it_on(): void
    {
        $u = $this->creator(true);
        $t = $this->tontine($u, false);

        $this->actingAs($u)->put(route('tontines.portal.update', $t), ['member_portal_enabled' => 1])
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $t->fresh()->member_portal_enabled);
    }

    public function test_the_personal_link_pages_send_privacy_headers(): void
    {
        $u = $this->creator(true);
        $token = $this->member($this->tontine($u))->issueAccessToken();

        $res = $this->get('/m/' . $token);

        $res->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag');
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
    }

    public function test_the_member_space_stops_once_the_creator_plan_is_gone(): void
    {
        $u = $this->creator(true);
        $token = $this->member($this->tontine($u))->issueAccessToken();

        $this->get('/m/' . $token)->assertRedirect(route('portal.home'));
        $this->get(route('portal.home'))->assertOk();

        $u->subscriptions()->update(['ends_at' => now()->subDays(30)]); // au-delà des jours de grâce

        $this->get(route('portal.home'))->assertForbidden();
    }

    public function test_a_legacy_tontine_keeps_its_member_space_on_the_free_plan(): void
    {
        $u = $this->creator();
        $token = $this->member($this->tontine($u, true, true))->issueAccessToken();

        $this->get('/m/' . $token)->assertRedirect(route('portal.home'));
        $this->get(route('portal.home'))->assertOk();
    }

    public function test_an_unknown_token_reveals_nothing(): void
    {
        $this->get('/m/jeton-ki-pa-egziste')->assertNotFound();
    }
}
