<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tontine;
use App\Models\User;
use App\Services\TrialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function draftTontine(): Tontine
    {
        $u = User::factory()->create();
        app(TrialService::class)->grant($u);

        return Tontine::create(['admin_id' => $u->id, 'name' => 'Sòl', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => 'draft']);
    }

    public function test_adding_a_member_without_consent_is_refused(): void
    {
        $t = $this->draftTontine();

        $this->actingAs($t->admin)->post(route('tontines.members.store', $t), ['name' => 'Marie', 'phone' => '+509 4700 1111'])
            ->assertSessionHasErrors('consent');

        $this->assertSame(0, Member::count());
    }

    public function test_adding_a_member_with_consent_records_when_it_was_given(): void
    {
        $t = $this->draftTontine();

        $this->actingAs($t->admin)->post(route('tontines.members.store', $t), ['name' => 'Marie', 'consent' => 1])
            ->assertSessionHasNoErrors();

        $m = Member::firstOrFail();
        $this->assertNotNull($m->consent_at);
        $this->assertTrue($m->consent_at->gte(now()->subMinute()));
    }

    public function test_the_privacy_page_is_public(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get(route('privacy'))->assertOk()->assertSee('POLITIQUE DE CONFIDENTIALITÉ')->assertSee('DURÉE DE CONSERVATION');
    }

    public function test_the_consent_checkbox_links_to_the_privacy_page(): void
    {
        $t = $this->draftTontine();

        $this->actingAs($t->admin)->get(route('tontines.members.index', $t))
            ->assertOk()->assertSee('name="consent"', false)->assertSee(route('privacy'), false);
    }

    public function test_a_member_never_sees_another_members_phone_number(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => false])->save();
        $plan = Plan::updateOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true, 'price_monthly' => 2500,
            'sort_order' => 3, 'max_active_tontines' => 10, 'max_members_per_tontine' => 50, 'features' => ['member_portal']]);
        Subscription::create(['user_id' => $u->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(),
            'ends_at' => now()->addMonth(), 'amount' => 0, 'source' => 'owner_grant']);

        $t = Tontine::create(['admin_id' => $u->id, 'name' => 'Sòl', 'amount_per_period' => 500, 'period_type' => 'weekly',
            'start_date' => today(), 'status' => 'active']);
        $t->forceFill(['member_portal_enabled' => true])->save();

        $me = $t->members()->create(['name' => 'Marie', 'position' => 1, 'is_active' => true, 'phone' => '+509 4700 1111']);
        $t->members()->create(['name' => 'Jean', 'position' => 2, 'is_active' => true, 'phone' => '+509 4822 2233']);
        $t->members()->create(['name' => 'Rose', 'position' => 3, 'is_active' => true, 'phone' => '+509 4933 3344']);

        $token = $me->issueAccessToken();
        $this->get('/m/' . $token);

        $this->get(route('portal.home'))->assertOk()
            ->assertDontSee('4822 2233')
            ->assertDontSee('4933 3344');
    }
}
