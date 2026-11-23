<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tontine;
use App\Models\User;
use App\Services\TrialService;
use App\Support\Entitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): Plan
    {
        return Plan::updateOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true, 'price_monthly' => 2500,
            'sort_order' => 3, 'max_active_tontines' => 10, 'max_members_per_tontine' => 50,
            'features' => ['member_portal', 'organizer_fee', 'csv_export', 'restart_round', 'whatsapp_reminders']]);
    }

    public function test_a_new_account_gets_a_three_month_pro_trial_when_it_registers(): void
    {
        $this->pro();

        $this->post('/register', ['name' => 'Nouvo Moun', 'email' => 'nouvo@example.com',
            'password' => 'NouvoModpas123', 'password_confirmation' => 'NouvoModpas123']);

        $sub = Subscription::whereHas('user', fn ($q) => $q->where('email', 'nouvo@example.com'))->firstOrFail();
        $this->assertSame('trial', $sub->source);
        $this->assertSame('Pro', $sub->plan->name);
        $this->assertTrue($sub->ends_at->between(now()->addMonths(3)->subMinute(), now()->addMonths(3)->addMinute()));
    }

    public function test_the_same_phone_cannot_get_a_second_trial(): void
    {
        $this->pro();
        $first = User::factory()->create(['phone' => '+509 4700 1111']);
        $second = User::factory()->create(['phone' => '+509 4700 1111']);

        $this->assertTrue(app(TrialService::class)->grant($first));
        $this->assertFalse(app(TrialService::class)->grant($second));
    }

    public function test_owners_never_get_a_trial(): void
    {
        $this->pro();
        $owner = User::factory()->create();
        $owner->forceFill(['is_owner' => true])->save();

        $this->assertFalse(app(TrialService::class)->grant($owner));
    }

    public function test_during_the_trial_the_organizer_can_create_tontines(): void
    {
        $this->pro();
        $u = User::factory()->create();
        app(TrialService::class)->grant($u);

        $this->assertTrue(Entitlements::for($u)->canCreateTontine());
    }

    public function test_without_a_subscription_everything_is_read_only_but_legacy_tontines_stay_usable(): void
    {
        $u = User::factory()->create();
        $normal = Tontine::create(['admin_id' => $u->id, 'name' => 'Nòmal', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => 'draft']);
        $legacy = Tontine::create(['admin_id' => $u->id, 'name' => 'Ansyen', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => 'draft']);
        $legacy->forceFill(['is_legacy' => true])->save();

        $ent = Entitlements::for($u);

        $this->assertFalse($ent->canCreateTontine());
        $this->assertFalse($ent->has('csv_export'));
        $this->assertFalse($ent->canAddMember($normal));
        $this->assertFalse($ent->isWritable($normal));
        $this->assertTrue($ent->isWritable($legacy));
        $this->assertSame(1, $ent->readOnlyCount());
    }

    public function test_subscription_reminder_defaults_to_seven_days(): void
    {
        $this->assertSame(7, Setting::subscriptionRules()['remind_days']);
    }
}
