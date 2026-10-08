<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Support\Entitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    private function openShop(): Plan
    {
        Plan::where('slug', 'pro')->update(['is_active' => true, 'price_monthly' => 2500, 'price_quarterly' => 6750, 'price_yearly' => 24000]);
        Setting::set('payment', ['moncash_number' => '+509 3712-8899', 'moncash_name' => 'LajanAnsanm', 'natcash_number' => null, 'natcash_name' => null, 'bank_info' => null, 'instructions' => null]);

        return Plan::where('slug', 'pro')->firstOrFail();
    }

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    private function submit(User $u, Plan $p, array $o = [])
    {
        return $this->actingAs($u)->post(route('plans.request.submit', $p), array_merge([
            'billing_period' => 'monthly', 'payment_method' => 'moncash', 'payer_phone' => '+509 3712 8899', 'transaction_ref' => 'MC-8942019',
        ], $o));
    }

    private function pendingFor(User $u, Plan $p, array $o = []): SubscriptionRequest
    {
        return SubscriptionRequest::create(array_merge([
            'user_id' => $u->id, 'plan_id' => $p->id, 'billing_period' => 'monthly', 'amount' => 2500, 'payment_method' => 'moncash',
            'payer_phone' => '+50937128899', 'transaction_ref' => 'T-' . uniqid(), 'reference_code' => SubscriptionRequest::nextReferenceCode($u),
        ], $o));
    }

    /* ---------- Côté client ---------- */

    public function test_only_active_plans_are_listed_and_guests_must_log_in(): void
    {
        $this->get(route('plans'))->assertRedirect(route('login'));

        $u = User::factory()->create();
        $this->actingAs($u)->get(route('plans'))->assertOk()->assertSee('Gratis')->assertDontSee('Esansyèl');

        $this->openShop();
        $this->actingAs($u)->get(route('plans'))->assertOk()->assertSee('Pro');
    }

    public function test_a_plan_that_cannot_be_bought_gives_a_404(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->get(route('plans.request', Plan::where('slug', 'pro')->first()))->assertNotFound();    // inactif, sans prix
        $this->actingAs($u)->get(route('plans.request', Plan::where('is_free', true)->first()))->assertNotFound();  // gratuit
        $this->openShop();
        $this->actingAs($u)->get(route('plans.request', Plan::where('slug', 'pro')->first()))->assertOk();
    }

    public function test_nobody_can_subscribe_until_a_payment_method_is_configured(): void
    {
        $pro = $this->openShop();
        Setting::set('payment', []);
        $u = User::factory()->create();

        $this->actingAs($u)->get(route('plans.request', $pro))->assertOk()->assertSee('data-no-payment', false);
        $this->submit($u, $pro)->assertSessionHasErrors('payment_method');
        $this->assertSame(0, SubscriptionRequest::count());
    }

    public function test_a_request_freezes_the_price_and_shows_the_same_reference_code(): void
    {
        $pro = $this->openShop();
        $u = User::factory()->create();

        $page = $this->actingAs($u)->get(route('plans.request', [$pro, 'period' => 'quarterly']))->assertOk();
        $code = sprintf('LA-%04d-001', $u->id);
        $page->assertSee($code)->assertSee('6,750');

        $this->submit($u, $pro, ['billing_period' => 'quarterly'])->assertRedirect(route('subscription'));

        $r = SubscriptionRequest::firstOrFail();
        $this->assertSame($code, $r->reference_code);
        $this->assertEquals(6750, $r->amount);
        $this->assertSame('pending', $r->status);
        $this->assertSame(1, ActivityLog::where('action', 'subscription_requested')->count());
        $this->actingAs($u)->get(route('subscription'))->assertOk()->assertSee('data-pending-request', false);
    }

    public function test_only_one_pending_request_at_a_time(): void
    {
        $pro = $this->openShop();
        $u = User::factory()->create();

        $this->submit($u, $pro);
        $this->submit($u, $pro, ['transaction_ref' => 'AUTRE'])->assertRedirect(route('subscription'));
        $this->actingAs($u)->get(route('plans.request', $pro))->assertRedirect(route('subscription'));

        $this->assertSame(1, SubscriptionRequest::count());
    }

    public function test_a_transaction_number_cannot_be_reused(): void
    {
        $pro = $this->openShop();
        $this->pendingFor(User::factory()->create(), $pro, ['transaction_ref' => 'MC-8942019']);

        $this->submit(User::factory()->create(), $pro, ['transaction_ref' => 'mc-8942019'])->assertSessionHasErrors('transaction_ref');
        $this->assertSame(1, SubscriptionRequest::count());
    }

    public function test_the_request_form_validates_its_input(): void
    {
        $pro = $this->openShop();
        $u = User::factory()->create();

        $this->submit($u, $pro, ['payer_phone' => 'abc'])->assertSessionHasErrors('payer_phone');
        $this->submit($u, $pro, ['transaction_ref' => ''])->assertSessionHasErrors('transaction_ref');
        $this->submit($u, $pro, ['payment_method' => 'bitcoin'])->assertSessionHasErrors('payment_method');
        $this->submit($u, $pro, ['billing_period' => 'weekly'])->assertSessionHasErrors('billing_period');
        $this->assertSame(0, SubscriptionRequest::count());

        Plan::where('slug', 'pro')->update(['price_yearly' => null]);
        $this->submit($u, $pro, ['billing_period' => 'yearly'])->assertSessionHasErrors('billing_period');
    }

    /* ---------- Côté propriétaire ---------- */

    public function test_approval_creates_the_subscription_with_the_right_dates(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $pro = $this->openShop();
        $u = User::factory()->create();
        $r = $this->pendingFor($u, $pro);

        $this->actingAs($this->owner())->post(route('admin.requests.approve', $r))->assertSessionHasNoErrors();

        $r->refresh();
        $this->assertSame('approved', $r->status);
        $sub = $r->subscription_id ? Subscription::find($r->subscription_id) : null;
        $this->assertSame('2026-11-10', $sub->ends_at->format('Y-m-d'));
        $e = Entitlements::for($u);
        $this->assertSame('active', $e->state);
        $this->assertSame('Pro', $e->plan->name);
        $this->assertSame(1, ActivityLog::where('action', 'subscription_activated')->count());

        Carbon::setTestNow();
    }

    public function test_quarterly_and_yearly_periods_and_a_custom_end_date(): void
    {
        Carbon::setTestNow('2026-01-31 10:00:00');
        $pro = $this->openShop();
        $owner = $this->owner();

        $q = $this->pendingFor($u1 = User::factory()->create(), $pro, ['billing_period' => 'quarterly']);
        $this->actingAs($owner)->post(route('admin.requests.approve', $q));
        $this->assertSame('2026-04-30', $u1->subscriptions()->first()->ends_at->format('Y-m-d'), 'pas de débordement : 31 janvier + 3 mois');

        $y = $this->pendingFor($u2 = User::factory()->create(), $pro, ['billing_period' => 'yearly']);
        $this->actingAs($owner)->post(route('admin.requests.approve', $y));
        $this->assertSame('2027-01-31', $u2->subscriptions()->first()->ends_at->format('Y-m-d'));

        $c = $this->pendingFor($u3 = User::factory()->create(), $pro);
        $this->actingAs($owner)->post(route('admin.requests.approve', $c), ['ends_at' => '2027-06-15']);
        $this->assertSame('2027-06-15', $u3->subscriptions()->first()->ends_at->format('Y-m-d'));

        $bad = $this->pendingFor(User::factory()->create(), $pro);
        $this->actingAs($owner)->post(route('admin.requests.approve', $bad), ['ends_at' => '2026-01-01'])->assertSessionHasErrors('ends_at');
        $this->assertSame('pending', $bad->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_renewing_the_same_plan_extends_from_the_current_end(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $pro = $this->openShop();
        $u = User::factory()->create();
        $old = Subscription::create(['user_id' => $u->id, 'plan_id' => $pro->id, 'status' => 'active', 'starts_at' => now()->subMonth(),
            'ends_at' => Carbon::parse('2026-10-20 10:00:00'), 'amount' => 0, 'source' => 'owner_grant']);

        $this->actingAs($this->owner())->post(route('admin.requests.approve', $this->pendingFor($u, $pro)));

        $this->assertSame('replaced', $old->fresh()->status);
        $e = Entitlements::for($u);
        $this->assertSame('2026-11-20', $e->subscription->ends_at->format('Y-m-d'));
        $this->assertSame(1, $u->subscriptions()->where('status', 'active')->count());

        Carbon::setTestNow();
    }

    public function test_upgrading_replaces_the_old_plan_immediately(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $pro = $this->openShop();
        $u = User::factory()->create();
        Subscription::create(['user_id' => $u->id, 'plan_id' => Plan::where('slug', 'esansyel')->value('id'), 'status' => 'active', 'starts_at' => now()->subMonth(),
            'ends_at' => Carbon::parse('2026-10-20 10:00:00'), 'amount' => 0, 'source' => 'owner_grant']);

        $this->actingAs($this->owner())->post(route('admin.requests.approve', $this->pendingFor($u, $pro)));

        $e = Entitlements::for($u);
        $this->assertSame('Pro', $e->plan->name);
        $this->assertSame('2026-11-10', $e->subscription->ends_at->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_a_request_cannot_be_processed_twice(): void
    {
        $pro = $this->openShop();
        $u = User::factory()->create();
        $r = $this->pendingFor($u, $pro);
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.requests.approve', $r))->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('admin.requests.approve', $r))->assertSessionHasErrors('request');
        $this->actingAs($owner)->post(route('admin.requests.reject', $r), ['note' => 'tard'])->assertSessionHasErrors('request');

        $this->assertSame(1, $u->subscriptions()->count());
        $this->assertSame('approved', $r->fresh()->status);
    }

    public function test_rejection_needs_a_reason_which_the_customer_can_read(): void
    {
        $pro = $this->openShop();
        $u = User::factory()->create();
        $r = $this->pendingFor($u, $pro);
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.requests.reject', $r), ['note' => ''])->assertSessionHasErrors('note');
        $this->assertSame('pending', $r->fresh()->status);

        $this->actingAs($owner)->post(route('admin.requests.reject', $r), ['note' => 'Montan an pa bon']);
        $this->assertSame('rejected', $r->fresh()->status);
        $this->assertSame(0, $u->subscriptions()->count());
        $this->actingAs($u)->get(route('subscription'))->assertOk()->assertSee('Montan an pa bon');
    }

    public function test_the_amount_stays_the_one_agreed_when_the_price_changes_later(): void
    {
        $pro = $this->openShop();
        $u = User::factory()->create();
        $r = $this->pendingFor($u, $pro);
        $pro->update(['price_monthly' => 9999]);

        $this->actingAs($this->owner())->post(route('admin.requests.approve', $r));

        $this->assertEquals(2500, $u->subscriptions()->first()->amount);
    }

    public function test_regular_users_cannot_process_requests(): void
    {
        $pro = $this->openShop();
        $r = $this->pendingFor($u = User::factory()->create(), $pro);

        $this->actingAs($u)->post(route('admin.requests.approve', $r))->assertNotFound();
        $this->actingAs($u)->post(route('admin.requests.reject', $r), ['note' => 'x'])->assertNotFound();
        $this->assertSame('pending', $r->fresh()->status);
    }

    public function test_the_owner_can_grant_and_revoke_a_subscription(): void
    {
        $owner = $this->owner();
        $u = User::factory()->create();
        $pro = Plan::where('slug', 'pro')->first();

        $this->actingAs($owner)->post(route('admin.users.grant', $u), ['plan_id' => $pro->id, 'ends_at' => today()->addMonths(2)->format('Y-m-d')])
            ->assertSessionHasNoErrors();
        $this->assertSame('active', Entitlements::for($u)->state);

        $this->actingAs($owner)->delete(route('admin.users.revoke', $u));
        $this->assertSame('free', Entitlements::for($u)->state);
        $this->assertSame(1, ActivityLog::where('action', 'subscription_cancelled')->count());

        $this->actingAs($owner)->post(route('admin.users.grant', $u), ['plan_id' => Plan::where('is_free', true)->value('id'), 'ends_at' => today()->addMonth()->format('Y-m-d')])
            ->assertSessionHasErrors('plan_id');
        $this->actingAs($owner)->post(route('admin.users.grant', $owner), ['plan_id' => $pro->id, 'ends_at' => today()->addMonth()->format('Y-m-d')])->assertForbidden();
    }

    public function test_the_requests_screen_lists_by_status_with_counts(): void
    {
        $pro = $this->openShop();
        $a = User::factory()->create(['name' => 'Anita Atant']);
        $b = User::factory()->create(['name' => 'Bruno Konfime']);
        $this->pendingFor($a, $pro);
        $this->pendingFor($b, $pro, ['status' => 'approved']);
        $owner = $this->owner();

        $res = $this->actingAs($owner)->get(route('admin.requests'))->assertOk()->assertSee('Anita Atant')->assertDontSee('Bruno Konfime');
        $this->assertEquals(1, $res->viewData('counts')['pending']);

        $this->actingAs($owner)->get(route('admin.requests', ['status' => 'approved']))->assertSee('Bruno Konfime');
        $this->actingAs($owner)->get(route('admin.requests', ['status' => 'hack']))->assertOk();
    }
}
