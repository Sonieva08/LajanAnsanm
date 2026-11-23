<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Entitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlanConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    private function data(array $o = []): array
    {
        return array_merge([
            'name' => 'Premium+', 'price_monthly' => 4000, 'max_active_tontines' => 20, 'max_members_per_tontine' => 80,
            'features' => ['csv_export'], 'is_active' => 1, 'sort_order' => 9,
        ], $o);
    }

    public function test_the_four_default_plans_exist_and_only_the_free_one_is_open(): void
    {
        $this->assertSame(['gratis', 'esansyel', 'pro', 'oganizasyon'], Plan::orderBy('sort_order')->pluck('slug')->all());
        $this->assertSame(['gratis'], Plan::listed()->pluck('slug')->all());

        $free = Plan::free();
        $this->assertSame([1, 10, []], [$free->max_active_tontines, $free->max_members_per_tontine, $free->features]);
    }

    public function test_the_owner_can_create_a_plan_without_touching_code(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->post(route('admin.plans.store'), $this->data())->assertRedirect(route('admin.plans'));

        $p = Plan::where('name', 'Premium+')->firstOrFail();
        $this->assertSame('premium', $p->slug);
        $this->assertSame(20, $p->max_active_tontines);
        $this->assertSame(['csv_export'], $p->features);
        $this->assertContains($p->slug, Plan::listed()->pluck('slug')->all());

        $this->actingAs($o)->post(route('admin.plans.store'), $this->data());
        $this->assertSame(['premium', 'premium-2'], Plan::where('slug', 'like', 'premium%')->orderBy('id')->pluck('slug')->all());
    }

    public function test_quarterly_and_yearly_prices_are_calculated_from_the_monthly_price(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.plans.store'), $this->data([
            'price_monthly' => 1235,
            'price_quarterly' => 1,
            'price_yearly' => 1,
        ]))->assertRedirect(route('admin.plans'));

        $plan = Plan::where('name', 'Premium+')->firstOrFail();

        $this->assertEquals(1235, $plan->price_monthly);
        $this->assertEquals(3594, $plan->price_quarterly);
        $this->assertEquals(13338, $plan->price_yearly);

        $this->actingAs($owner)->put(route('admin.plans.update', $plan), $this->data([
            'price_monthly' => 2000,
            'price_quarterly' => 1,
            'price_yearly' => 1,
        ]))->assertRedirect(route('admin.plans'));

        $this->assertEquals(5820, $plan->fresh()->price_quarterly);
        $this->assertEquals(21600, $plan->fresh()->price_yearly);
    }

    public function test_plan_prices_show_monthly_equivalent_period_selection_and_billing_amounts(): void
    {
        $user = User::factory()->create();
        Plan::where('slug', 'pro')->update([
            'is_active' => true,
            'price_monthly' => 1000,
            'price_quarterly' => 2910,
            'price_yearly' => 10800,
        ]);

        $this->actingAs($user)->get(route('plans'))
            ->assertOk()
            ->assertSee('data-period-switch', false)
            ->assertSee('data-monthly-equivalent', false)
            ->assertSee('data-billing-summary', false)
            ->assertSee('Peye chak mwa')
            ->assertSee('Peye chak 3 mwa')
            ->assertSee('Peye chak ane')
            ->assertSee('2910')
            ->assertSee('10800');
    }

    public function test_the_form_validates_prices_limits_and_features(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->post(route('admin.plans.store'), $this->data(['name' => '']))->assertSessionHasErrors('name');
        $this->actingAs($o)->post(route('admin.plans.store'), $this->data(['price_monthly' => -5]))->assertSessionHasErrors('price_monthly');
        $this->actingAs($o)->post(route('admin.plans.store'), $this->data(['price_monthly' => 1234.56]))->assertSessionHasErrors('price_monthly');
        $this->actingAs($o)->post(route('admin.plans.store'), $this->data(['price_monthly' => null]))->assertSessionHasErrors('price_monthly'); // actif sans prix
        $this->actingAs($o)->post(route('admin.plans.store'), $this->data(['max_active_tontines' => null]))->assertSessionHasErrors('max_active_tontines');
        $this->actingAs($o)->post(route('admin.plans.store'), $this->data(['features' => ['hack']]))->assertSessionHasErrors('features.0');
        $this->actingAs($o)->post(route('admin.plans.store'), $this->data(['max_members_per_tontine' => 1]))->assertSessionHasErrors('max_members_per_tontine');

        $this->assertSame(4, Plan::count());
    }

    public function test_unlimited_checkboxes_store_null(): void
    {
        $o = $this->owner();
        $this->actingAs($o)->post(route('admin.plans.store'), $this->data(['max_active_tontines' => null, 'unlimited_tontines' => 1, 'unlimited_members' => 1, 'max_members_per_tontine' => null]));

        $p = Plan::where('name', 'Premium+')->firstOrFail();
        $this->assertNull($p->max_active_tontines);
        $this->assertNull($p->max_members_per_tontine);
    }

    public function test_editing_a_plan_keeps_the_current_subscription_benefits_until_renewal(): void
    {
        $o = $this->owner();
        $pro = Plan::where('slug', 'pro')->first();
        $u = User::factory()->create();
        Subscription::create([
            'user_id' => $u->id, 'plan_id' => $pro->id, 'status' => 'active',
            'starts_at' => now(), 'ends_at' => now()->addMonth(), 'amount' => 0, 'source' => 'owner_grant',
            'plan_snapshot' => $pro->snapshotData(),
        ]);
        $this->assertTrue(Entitlements::for($u)->has('organizer_fee'));

        $this->actingAs($o)->put(route('admin.plans.update', $pro), $this->data(['name' => 'Pro', 'features' => ['csv_export'], 'max_members_per_tontine' => 30]))
            ->assertSessionHasNoErrors();

        $e = Entitlements::for($u);
        $this->assertTrue($e->has('organizer_fee'));
        $this->assertSame(50, $e->maxMembers());
        $this->assertSame(1, ActivityLog::where('action', 'plan_updated')->count());
        $this->actingAs($o)->get(route('admin.plans.edit', $pro))
            ->assertOk()
            ->assertSee('abòne aktif')
            ->assertSee('nouvo limit ak fonksyon yo ap aplike lè yo renouvle');
    }

    public function test_plan_delete_form_is_outside_the_update_form(): void
    {
        $owner = $this->owner();
        $plan = Plan::where('slug', 'pro')->firstOrFail();

        $html = $this->actingAs($owner)
            ->get(route('admin.plans.edit', $plan))
            ->assertOk()
            ->getContent();

        $updateFormStart = strpos($html, 'data-plan-update-form');
        $updateFormEnd = strpos($html, '</form>', $updateFormStart);
        $deleteFormStart = strpos($html, 'data-plan-delete-form');

        $this->assertNotFalse($updateFormStart);
        $this->assertNotFalse($updateFormEnd);
        $this->assertNotFalse($deleteFormStart);
        $this->assertGreaterThan($updateFormEnd, $deleteFormStart);
    }

    public function test_the_free_plan_stays_free_and_active(): void
    {
        $o = $this->owner();
        $free = Plan::free();

        $this->actingAs($o)->put(route('admin.plans.update', $free), $this->data(['name' => 'Gratis', 'is_active' => 0, 'price_monthly' => 999, 'max_active_tontines' => 2]));

        $free->refresh();
        $this->assertTrue($free->is_active);
        $this->assertEquals(0, $free->price_monthly);
        $this->assertSame(2, $free->max_active_tontines);
        $this->actingAs($o)->post(route('admin.plans.toggle', $free))->assertSessionHasErrors('plan');
        $this->actingAs($o)->delete(route('admin.plans.destroy', $free))->assertSessionHasErrors('plan');
    }

    public function test_a_paid_plan_cannot_go_live_without_a_price(): void
    {
        $o = $this->owner();
        $esa = Plan::where('slug', 'esansyel')->first();

        $this->actingAs($o)->post(route('admin.plans.toggle', $esa))->assertSessionHasErrors('plan');
        $this->assertFalse($esa->fresh()->is_active);

        $esa->update(['price_monthly' => 1200]);
        $this->actingAs($o)->post(route('admin.plans.toggle', $esa))->assertSessionHasNoErrors();
        $this->assertTrue($esa->fresh()->is_active);
        $this->actingAs($o)->post(route('admin.plans.toggle', $esa));
        $this->assertFalse($esa->fresh()->is_active);
    }

    public function test_a_plan_in_use_cannot_be_deleted(): void
    {
        $o = $this->owner();
        $esa = Plan::where('slug', 'esansyel')->first();
        Subscription::create(['user_id' => User::factory()->create()->id, 'plan_id' => $esa->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth(), 'amount' => 0, 'source' => 'owner_grant']);

        $this->actingAs($o)->delete(route('admin.plans.destroy', $esa))->assertSessionHasErrors('plan');
        $this->assertNotNull(Plan::find($esa->id));

        $unused = Plan::where('slug', 'oganizasyon')->first();
        $this->actingAs($o)->delete(route('admin.plans.destroy', $unused))->assertRedirect(route('admin.plans'));
        $this->assertNull(Plan::find($unused->id));
    }

    public function test_grace_days_and_payment_details_are_saved_and_used(): void
    {
        $o = $this->owner();
        Storage::fake('local');

        $this->actingAs($o)->post(route('admin.plans.rules'), [
            'grace_days' => 10, 'remind_days' => 3, 'moncash_number' => '+509 1111 2222', 'moncash_name' => 'Chef', 'natcash_number' => '', 'bank_info' => '',
            'moncash_qr' => UploadedFile::fake()->createWithContent(
                'moncash.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=', true)
            ),
        ])->assertSessionHasNoErrors();

        $this->assertSame(['grace_days' => 10, 'remind_days' => 3], Setting::subscriptionRules());
        $this->assertSame(['moncash' => 'MonCash'], Setting::paymentMethods());

        $this->actingAs($o)->post(route('admin.plans.rules'), ['grace_days' => 61, 'remind_days' => 3])->assertSessionHasErrors('grace_days');
        $this->actingAs($o)->post(route('admin.plans.rules'), ['grace_days' => -1, 'remind_days' => 3])->assertSessionHasErrors('grace_days');
    }

    public function test_the_console_screens_render_and_the_sidebar_counts_pending_requests(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->get(route('admin.plans'))->assertOk()->assertSee('Gratis')->assertSee('Esansyèl')->assertSee('Òganizasyon');
        $this->actingAs($o)->get(route('admin.plans.create'))->assertOk();
        $this->actingAs($o)->get(route('admin.requests'))->assertOk();
    }

    public function test_the_owner_can_inspect_online_subscriptions_and_recent_payment_attempts(): void
    {
        $owner = $this->owner();
        $user = User::factory()->create(['name' => 'Sandbox Customer', 'email' => 'sandbox@example.com']);
        $plan = Plan::where('slug', 'pro')->firstOrFail();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_period' => 'monthly',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 2500,
            'source' => 'online',
            'note' => 'MonCashConnect SUB-SANDBOX-123',
        ]);
        Subscription::create([
            'user_id' => User::factory()->create(['name' => 'Manual Customer'])->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_period' => 'monthly',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 2500,
            'source' => 'request',
        ]);
        SubscriptionPayment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_period' => 'monthly',
            'amount' => 2500,
            'provider' => 'moncashconnect',
            'reference_id' => 'SUB-SANDBOX-123',
            'status' => 'completed',
            'completed_at' => now(),
            'subscription_id' => $subscription->id,
        ]);
        SubscriptionPayment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_period' => 'monthly',
            'amount' => 2500,
            'provider' => 'moncashconnect',
            'reference_id' => 'SUB-SANDBOX-PENDING',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.subscriptions', ['source' => 'online']))
            ->assertOk()
            ->assertSee('Sandbox Customer')
            ->assertSee('sandbox@example.com')
            ->assertDontSee('Manual Customer')
            ->assertSee('SUB-SANDBOX-123')
            ->assertSee('SUB-SANDBOX-PENDING')
            ->assertSee('MonCashConnect');
    }

    public function test_non_owners_cannot_open_the_subscription_console(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.subscriptions'))
            ->assertNotFound();
    }
}
