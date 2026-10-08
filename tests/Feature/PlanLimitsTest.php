<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tontine;
use App\Models\User;
use App\Support\Entitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function payload(array $o = []): array
    {
        return array_merge(['name' => 'Sòl', 'amount_per_period' => 500, 'period_type' => 'weekly', 'start_date' => today()->format('Y-m-d')], $o);
    }

    private function sub(User $u, string $endsAt, string $slug = 'pro'): Subscription
    {
        return Subscription::create([
            'user_id' => $u->id, 'plan_id' => Plan::where('slug', $slug)->value('id'), 'status' => 'active',
            'starts_at' => now()->subYear(), 'ends_at' => Carbon::parse($endsAt), 'amount' => 0, 'source' => 'owner_grant',
        ]);
    }

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    /* ---------- Limites du plan gratuit ---------- */

    public function test_a_free_user_can_create_one_tontine_then_is_sent_to_the_plans_page(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->post(route('tontines.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(1, $u->tontines()->count());

        $this->actingAs($u)->post(route('tontines.store'), $this->payload(['name' => 'Deux']))
            ->assertRedirect(route('plans'))->assertSessionHas('limit.need', 'tontines')->assertSessionHas('limit.max', 1);

        $this->assertSame(1, $u->tontines()->count());
    }

    public function test_completed_and_legacy_tontines_do_not_count(): void
    {
        $u = User::factory()->create();
        $done = $this->makeTontine($u, 3, [], false);
        $done->update(['status' => 'completed']);

        $this->actingAs($u)->post(route('tontines.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(2, $u->tontines()->count());

        $legacy = User::factory()->create();
        $this->makeTontine($legacy, 3, [], false)->forceFill(['is_legacy' => true])->save();
        $this->actingAs($legacy)->post(route('tontines.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(2, $legacy->tontines()->count());
    }

    public function test_the_member_limit_is_enforced_on_the_server(): void
    {
        $u = User::factory()->create();
        $t = $this->makeTontine($u, 10, [], false);

        $this->actingAs($u)->post(route('tontines.members.store', $t), ['name' => 'Onzyèm'])
            ->assertRedirect(route('plans'))->assertSessionHas('limit.need', 'members')->assertSessionHas('limit.used', 10);

        $this->assertSame(10, $t->members()->count());
    }

    public function test_legacy_tontines_ignore_the_member_limit(): void
    {
        $u = User::factory()->create();
        $t = $this->makeTontine($u, 10, [], false);
        $t->forceFill(['is_legacy' => true])->save();

        $this->actingAs($u)->post(route('tontines.members.store', $t), ['name' => 'Onzyèm'])->assertSessionHasNoErrors();
        $this->assertSame(11, $t->members()->count());
    }

    public function test_a_paid_plan_raises_the_limits_and_null_means_unlimited(): void
    {
        $u = $this->premiumUser();
        $this->makeTontine($u, 3);
        $this->actingAs($u)->post(route('tontines.store'), $this->payload())->assertSessionHasNoErrors();

        Plan::where('slug', 'pro')->update(['max_active_tontines' => null, 'max_members_per_tontine' => null]);
        $this->assertTrue(Entitlements::for($u)->canCreateTontine());
        $this->assertNull(Entitlements::for($u)->maxMembers());
    }

    public function test_the_owner_is_never_limited(): void
    {
        $o = $this->owner();

        foreach (range(1, 3) as $i) {
            $this->actingAs($o)->post(route('tontines.store'), $this->payload(['name' => "T$i"]))->assertSessionHasNoErrors();
        }
        $this->assertSame(3, $o->tontines()->count());
    }

    public function test_json_clients_get_a_403_instead_of_a_redirect(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->post(route('tontines.store'), $this->payload());

        $this->actingAs($u)->postJson(route('tontines.store'), $this->payload(['name' => 'Deux']))
            ->assertForbidden()->assertJson(['need' => 'tontines']);
    }

    /* ---------- Fonctions réservées ---------- */

    public function test_features_are_blocked_on_the_free_plan(): void
    {
        $u = User::factory()->create();
        $t = $this->start($this->makeTontine($u, 3, [], false));

        $this->actingAs($u)->get(route('tontines.reports.round', [$t, 1]))->assertOk();
        $this->actingAs($u)->get(route('tontines.reports.round', [$t, 1, 'format' => 'csv']))
            ->assertRedirect(route('plans'))->assertSessionHas('limit.need', 'csv_export');

        $t->update(['status' => 'completed']);
        $this->actingAs($u)->post(route('tontines.cycles.restart', $t), ['start_date' => today()->format('Y-m-d')])
            ->assertRedirect(route('plans'))->assertSessionHas('limit.need', 'restart_round');
        $this->assertSame(1, $t->fresh()->current_round);
    }

    public function test_the_organizer_fee_cannot_be_set_without_the_feature(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->post(route('tontines.store'), $this->payload(['organizer_fee_percent' => 20]))->assertSessionHasNoErrors();

        $this->assertEquals(0, $u->tontines()->first()->organizer_fee_percent);
    }

    public function test_whatsapp_reminders_are_a_paid_feature(): void
    {
        $free = User::factory()->create();
        $t = $this->start($this->makeTontine($free, 3, [], false));
        $this->actingAs($free)->get(route('tontines.contributions.index', $t))->assertOk()->assertDontSee('wa.me');

        $pro = $this->start($this->makeTontine());
        $this->actingAs($pro->admin)->get(route('tontines.contributions.index', $pro))->assertOk()->assertSee('wa.me');
    }

    public function test_the_locked_fee_field_and_restart_card_show_an_upsell(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get(route('tontines.create'))->assertOk()->assertSee('data-fee-locked', false);

        $t = $this->makeTontine($u, 2, [], false);
        $t = $this->start($t);
        $t->update(['status' => 'completed']);
        $this->actingAs($u)->get(route('tontines.cycles.index', $t))->assertOk()->assertSee('data-restart-locked', false);
    }

    /* ---------- États de l'abonnement ---------- */

    public function test_subscription_states_and_the_grace_boundaries(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');
        $u = User::factory()->create();
        $this->assertSame('free', Entitlements::for($u)->state);

        $s = $this->sub($u, '2026-10-13 12:00:00');
        $e = Entitlements::for($u);
        $this->assertSame('active', $e->state);
        $this->assertSame(3, $e->daysLeft());

        $s->update(['ends_at' => '2026-10-09 12:00:00']);              // fini hier
        $e = Entitlements::for($u);
        $this->assertSame('grace', $e->state);
        $this->assertSame(6, $e->graceDaysLeft());
        $this->assertSame('Pro', $e->plan->name, 'pendant la grâce, tous les avantages restent');

        $s->update(['ends_at' => '2026-10-03 12:00:00']);              // fini il y a exactement 7 jours : dernier instant de grâce
        $this->assertSame('grace', Entitlements::for($u)->state);

        $s->update(['ends_at' => '2026-10-03 11:59:59']);              // une seconde de plus
        $e = Entitlements::for($u);
        $this->assertSame('expired', $e->state);
        $this->assertSame('Gratis', $e->plan->name);

        Carbon::setTestNow();
    }

    public function test_the_grace_period_is_editable_from_the_console_settings(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');
        $u = User::factory()->create();
        $this->sub($u, '2026-10-05 12:00:00');                         // fini il y a 5 jours

        $this->assertSame('grace', Entitlements::for($u)->state);

        Setting::set('subscription', ['grace_days' => 3, 'remind_days' => 5]);
        $this->assertSame('expired', Entitlements::for($u)->state);

        Carbon::setTestNow();
    }

    /* ---------- Lecture seule après expiration ---------- */

    public function test_tontines_beyond_the_free_limit_become_read_only_after_expiry(): void
    {
        $u = $this->premiumUser();
        $first = $this->makeTontine($u, 3);
        $second = $this->makeTontine($u, 3);
        $u->subscriptions()->update(['ends_at' => now()->subDays(30)]);

        $this->actingAs($u)->post(route('tontines.members.store', $second), ['name' => 'Bloke'])
            ->assertSessionHas('subscription_notice');
        $this->assertSame(3, $second->members()->count());

        $this->actingAs($u)->post(route('tontines.members.store', $first), ['name' => 'Pase'])->assertSessionHasNoErrors();
        $this->assertSame(4, $first->members()->count());

        $this->actingAs($u)->get(route('tontines.show', $second))->assertOk();
        $this->actingAs($u)->get(route('tontines.members.index', $second))->assertOk();

        $this->actingAs($u)->delete(route('tontines.destroy', $second))->assertRedirect(route('tontines.index'));
        $this->assertNull(Tontine::find($second->id));
    }

    public function test_a_tontine_over_the_member_limit_is_read_only(): void
    {
        $u = $this->premiumUser();
        $t = $this->makeTontine($u, 12);
        $u->subscriptions()->update(['ends_at' => now()->subDays(30)]);

        $this->actingAs($u)->post(route('tontines.members.shuffle', $t))->assertSessionHas('subscription_notice');
        $this->assertFalse(Entitlements::for($u)->isWritable($t));
        $this->assertSame(1, Entitlements::for($u)->readOnlyCount());
    }

    public function test_read_only_blocks_every_write_but_not_reading(): void
    {
        $u = $this->premiumUser();
        $this->makeTontine($u, 3);
        $t = $this->start($this->makeTontine($u, 3));
        $u->subscriptions()->update(['ends_at' => now()->subDays(30)]);

        $cycle = $t->roundCycles()->first();
        $this->pay($t, $cycle, $t->members()->first())->assertSessionHas('subscription_notice');
        $this->payout($t, $cycle, true)->assertSessionHas('subscription_notice');
        $this->actingAs($u)->put(route('tontines.update', $t), $this->payload(['name' => 'Nouvo']))->assertSessionHas('subscription_notice');

        $this->assertSame(0, \App\Models\Contribution::count());
        $this->actingAs($u)->get(route('tontines.contributions.index', $t))->assertOk();
        $this->actingAs($u)->get(route('tontines.reports.index', $t))->assertOk();
    }

    /* ---------- Bannières ---------- */

    public function test_the_banners_follow_the_subscription_state(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');

        $far = $this->premiumUser();
        $this->actingAs($far)->get('/dashboard')->assertOk()->assertDontSee('data-expiring-banner', false)->assertDontSee('data-grace-banner', false);

        $soon = User::factory()->create();
        $this->sub($soon, '2026-10-13 12:00:00');
        $this->actingAs($soon)->get('/dashboard')->assertSee('data-expiring-banner', false);

        $grace = User::factory()->create();
        $this->sub($grace, '2026-10-08 12:00:00');
        $this->actingAs($grace)->get('/dashboard')->assertSee('data-grace-banner', false);

        $gone = User::factory()->create();
        $this->sub($gone, '2026-08-01 12:00:00');
        $this->makeTontine($gone, 3, [], false);
        $this->makeTontine($gone, 3, [], false);
        $this->actingAs($gone)->get('/dashboard')->assertSee('data-expired-banner', false);

        $this->actingAs($this->owner())->get('/dashboard')->assertDontSee('data-grace-banner', false)->assertDontSee('data-expiring-banner', false);

        Carbon::setTestNow();
    }

    public function test_the_discount_is_computed_from_the_monthly_price(): void
    {
        $p = new Plan(['price_monthly' => 1000, 'price_quarterly' => 2700, 'price_yearly' => 9600]);

        $this->assertSame(10, $p->discountPercent('quarterly'));
        $this->assertSame(20, $p->discountPercent('yearly'));
        $this->assertNull($p->discountPercent('monthly'));
        $this->assertNull((new Plan(['price_monthly' => 1000, 'price_quarterly' => 3000]))->discountPercent('quarterly'));
    }
}
