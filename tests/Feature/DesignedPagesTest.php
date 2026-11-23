<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\SubscriptionRequest;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class DesignedPagesTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function owner(): User
    {
        $u = User::factory()->create(['name' => 'Chèf Sistèm']);
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    private function req(User $u, array $o = []): SubscriptionRequest
    {
        return SubscriptionRequest::create(array_merge([
            'user_id' => $u->id, 'plan_id' => Plan::where('slug', 'pro')->value('id'), 'billing_period' => 'monthly', 'amount' => 2500,
            'payment_method' => 'moncash', 'payer_phone' => '+50937128899', 'transaction_ref' => 'T-' . uniqid(),
            'reference_code' => SubscriptionRequest::nextReferenceCode($u),
        ], $o));
    }

    /* ---------- Console : paiement et règles ---------- */

    public function test_the_payment_page_warns_when_no_method_is_configured(): void
    {
        $this->actingAs($this->owner())->get(route('admin.plans.payment'))
            ->assertOk()->assertSee('data-no-method', false)->assertSee('Pa konfigire');
    }

    public function test_the_payment_page_shows_each_method_status_and_a_client_preview(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('subscription-payment-qrs/moncash.png', 'QR image');
        Setting::set('payment', [
            'moncash_number' => '+509 3712-3456', 'moncash_name' => 'Chèf',
            'moncash_qr_path' => 'subscription-payment-qrs/moncash.png', 'natcash_number' => null,
        ]);

        $this->actingAs($this->owner())->get(route('admin.plans.payment'))
            ->assertOk()->assertDontSee('data-no-method', false)->assertSee('Konfigire')->assertSee('+509 3712-3456')
            ->assertSee('images/moncash_1.png')->assertSee('images/natcash_1.jpg');
    }

    public function test_the_page_makes_no_claim_about_features_that_do_not_exist(): void
    {
        $this->actingAs($this->owner())->get(route('admin.plans.payment'))
            ->assertOk()->assertDontSee('256-bit')->assertDontSee('SMS')->assertDontSee('24/7');
    }

    public function test_saving_the_rules_records_who_and_when_and_returns_to_the_page(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.plans.rules'), [
            'grace_days' => 7, 'remind_days' => 5, 'moncash_number' => '+509 1111 2222',
            'moncash_qr' => UploadedFile::fake()->createWithContent(
                'moncash.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=', true)
            ),
        ])
            ->assertRedirect(route('admin.plans.payment'));

        $this->assertSame('Chèf Sistèm', Setting::rulesMeta()['by']);
        $this->actingAs($owner)->get(route('admin.plans.payment'))->assertSee('Chèf Sistèm');
    }

    public function test_the_summary_figures_are_real(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $pro = Plan::where('slug', 'pro')->first();
        Subscription::create(['user_id' => $a->id, 'plan_id' => $pro->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth(), 'amount' => 0, 'source' => 'owner_grant']);
        Subscription::create(['user_id' => $b->id, 'plan_id' => $pro->id, 'status' => 'active', 'starts_at' => now()->subYear(), 'ends_at' => now()->subMonth(), 'amount' => 0, 'source' => 'owner_grant']);
        $this->req($a, ['status' => 'approved', 'reviewed_at' => now(), 'amount' => 3000]);
        $this->req($a, ['status' => 'approved', 'reviewed_at' => now()->subMonths(2), 'amount' => 999]);
        $this->req($b);

        $stats = $this->actingAs($this->owner())->get(route('admin.plans.payment'))->viewData('stats');

        $this->assertSame(1, $stats['active']);
        $this->assertSame(1, $stats['pending']);
        $this->assertEquals(3000, $stats['revenue']);
    }

    public function test_the_plans_list_links_to_the_payment_tab_without_the_rules_form(): void
    {
        $this->actingAs($this->owner())->get(route('admin.plans'))
            ->assertOk()->assertSee(route('admin.plans.payment'), false)->assertDontSee('name="grace_days"', false);
    }

    /* ---------- Page « Abònman mwen » ---------- */

    public function test_a_free_user_sees_the_free_hero_and_real_usage(): void
    {
        $u = User::factory()->create();
        $this->makeTontine($u, 7, [], false);

        $res = $this->actingAs($u)->get(route('subscription'))->assertOk()->assertSee('data-plan-hero', false)->assertSee('GRATIS');

        $this->assertSame(7, $res->viewData('membersUsed'));
        $res->assertSee('1 / 1');
        $res->assertSee('7 / 10');
    }

    public function test_usage_shows_the_highest_member_count_across_tontines(): void
    {
        $u = $this->premiumUser();
        $this->makeTontine($u, 4);
        $this->makeTontine($u, 9);

        $this->assertSame(9, $this->actingAs($u)->get(route('subscription'))->viewData('membersUsed'));
    }

    public function test_the_page_never_promises_sms_credits_that_do_not_exist(): void
    {
        $this->actingAs($this->premiumUser())->get(route('subscription'))->assertOk()->assertDontSee('SMS');
    }

    public function test_the_pending_card_and_the_rejection_reason_are_shown(): void
    {
        $u = User::factory()->create();
        $this->req($u, ['status' => 'rejected', 'review_note' => 'Kòd la pa koresponn', 'transaction_ref' => 'OLD-1']);
        $this->req($u, ['transaction_ref' => 'MC-8942-X771']);

        $this->actingAs($u)->get(route('subscription'))->assertOk()
            ->assertSee('data-pending-request', false)->assertSee('MC-8942-X771')->assertSee('Kòd la pa koresponn');
    }

    public function test_the_support_button_only_appears_when_a_number_is_configured(): void
    {
        $u = User::factory()->create();

        config(['lajanansanm.support_whatsapp' => null]);
        $this->actingAs($u)->get(route('subscription'))->assertDontSee('wa.me');

        config(['lajanansanm.support_whatsapp' => '+509 3100-0000']);
        $this->actingAs($u)->get(route('subscription'))->assertSee('https://wa.me/50931000000', false);
    }

    public function test_a_user_only_sees_their_own_requests(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->req($b, ['transaction_ref' => 'SEKRE-B-123']);

        $this->actingAs($a)->get(route('subscription'))->assertOk()->assertDontSee('SEKRE-B-123');
    }
}
