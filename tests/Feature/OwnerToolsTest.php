<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerToolsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // « 12345678901234567890 » en base32

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    private function ann(array $o = []): Announcement
    {
        return Announcement::create(array_merge(
            ['title' => 'Antretyen', 'message' => 'Mizajou jodi a', 'type' => 'info', 'audience' => 'all', 'is_active' => true],
            $o
        ));
    }

    /* ---------- TOTP ---------- */

    public function test_totp_matches_the_rfc6238_reference_vector(): void
    {
        $this->assertSame('287082', Totp::code(self::SECRET, 59, 6));
        $this->assertSame('287082', Totp::code(self::SECRET, 59));
    }

    public function test_totp_accepts_one_step_of_clock_drift_and_nothing_more(): void
    {
        $this->assertTrue(Totp::verify(self::SECRET, '287082', 1, 59 + 30));
        $this->assertFalse(Totp::verify(self::SECRET, '287082', 1, 59 + 120));
        $this->assertFalse(Totp::verify(self::SECRET, '28708', 1, 59));
        $this->assertFalse(Totp::verify(self::SECRET, 'abcdef', 1, 59));
    }

    public function test_the_secret_is_stored_encrypted_and_never_serialized(): void
    {
        $o = $this->owner();
        $o->forceFill(['two_factor_secret' => Totp::generateSecret()])->save();

        $this->assertArrayNotHasKey('two_factor_secret', $o->fresh()->toArray());
        $this->assertNotSame($o->fresh()->two_factor_secret, \DB::table('users')->where('id', $o->id)->value('two_factor_secret'));
    }

    /* ---------- Anons ---------- */

    public function test_announcements_reach_only_the_right_audience_and_dates(): void
    {
        $free = User::factory()->create();
        $paid = User::factory()->create();
        $pro = Plan::updateOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true, 'price_monthly' => 2500,
            'sort_order' => 3, 'features' => ['member_portal']]);
        Subscription::create(['user_id' => $paid->id, 'plan_id' => $pro->id, 'status' => 'active', 'starts_at' => now(),
            'ends_at' => now()->addMonth(), 'amount' => 0, 'source' => 'owner_grant']);

        $this->ann(['title' => 'Tout moun', 'audience' => 'all']);
        $this->ann(['title' => 'Gratis sèlman', 'audience' => 'free']);
        $this->ann(['title' => 'Abonè sèlman', 'audience' => 'paid']);
        $this->ann(['title' => 'Ekspire', 'ends_at' => now()->subDay()]);
        $this->ann(['title' => 'Kache', 'is_active' => false]);
        $this->ann(['title' => 'Pa kòmanse', 'starts_at' => now()->addWeek()]);

        $this->assertEqualsCanonicalizing(['Tout moun', 'Gratis sèlman'], Announcement::visibleTo($free)->pluck('title')->all());
        $this->assertEqualsCanonicalizing(['Tout moun', 'Abonè sèlman'], Announcement::visibleTo($paid)->pluck('title')->all());
    }

    public function test_the_banner_appears_on_the_dashboard(): void
    {
        $this->ann(['title' => 'Mizajou sistèm']);

        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()->assertSee('Mizajou sistèm')->assertSee('data-announcement', false);
    }

    public function test_the_owner_publishes_hides_and_removes_an_announcement(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->post(route('admin.announcements.store'), ['title' => 'Nouvo', 'message' => 'Tèks', 'type' => 'warning', 'audience' => 'all'])
            ->assertSessionHasNoErrors();
        $a = Announcement::firstOrFail();
        $this->assertSame('warning', $a->type);

        $this->actingAs($o)->post(route('admin.announcements.toggle', $a));
        $this->assertFalse($a->fresh()->is_active);

        $this->actingAs($o)->delete(route('admin.announcements.destroy', $a));
        $this->assertSame(0, Announcement::count());
    }

    public function test_announcement_inputs_are_validated_and_regular_users_are_refused(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->post(route('admin.announcements.store'), ['title' => 'X', 'message' => 'Y', 'type' => 'hack', 'audience' => 'all'])
            ->assertSessionHasErrors('type');
        $this->actingAs($o)->post(route('admin.announcements.store'), ['title' => 'X', 'message' => 'Y', 'type' => 'info', 'audience' => 'all',
            'starts_at' => '2026-10-10', 'ends_at' => '2026-10-01'])->assertSessionHasErrors('ends_at');
        $this->assertSame(0, Announcement::count());

        $this->actingAs(User::factory()->create())->get(route('admin.announcements'))->assertNotFound();
    }

    /* ---------- Istorik ---------- */

    public function test_changes_to_the_rules_appear_in_the_history(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->post(route('admin.plans.rules'), ['grace_days' => 7, 'remind_days' => 5])->assertRedirect();

        $this->actingAs($o)->get(route('admin.history'))->assertOk()->assertSee('Règ modifye');
    }

    public function test_the_history_is_owner_only(): void
    {
        $this->get(route('admin.history'))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('admin.history'))->assertNotFound();
    }

    /* ---------- Paramèt : onglets réels ---------- */

    public function test_the_settings_tabs_are_real_links_not_coming_soon_labels(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->get(route('admin.settings'))->assertOk()
            ->assertDontSee('Byento')
            ->assertSee(route('admin.settings.security'), false)
            ->assertSee(route('admin.settings.notifications'), false)
            ->assertSee(route('admin.plans.payment'), false);
    }

    /* ---------- Double vérification ---------- */

    public function test_two_factor_is_set_up_confirmed_and_then_required(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->post(route('admin.settings.security.setup'))->assertSessionHasNoErrors();
        $o->refresh();
        $this->assertNotNull($o->two_factor_secret);
        $this->assertNull($o->two_factor_confirmed_at);

        $this->actingAs($o)->post(route('admin.settings.security.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->actingAs($o)->post(route('admin.settings.security.confirm'), ['code' => Totp::code($o->two_factor_secret)])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($o->fresh()->two_factor_confirmed_at);

        // Nouvelle session : la console demande le code
        $this->app['session']->flush();
        $this->actingAs($o)->get(route('admin.overview'))->assertRedirect(route('admin.security.verify'));

        $this->actingAs($o)->post(route('admin.security.verify.submit'), ['code' => Totp::code($o->fresh()->two_factor_secret)])
            ->assertRedirect();
        $this->actingAs($o)->get(route('admin.overview'))->assertOk();
    }

    public function test_a_wrong_code_does_not_open_the_console(): void
    {
        $o = $this->owner();
        $o->forceFill(['two_factor_secret' => self::SECRET, 'two_factor_confirmed_at' => now()])->save();
        $this->app['session']->flush();

        $this->actingAs($o)->post(route('admin.security.verify.submit'), ['code' => '000001'])->assertSessionHasErrors('code');
        $this->actingAs($o)->get(route('admin.overview'))->assertRedirect(route('admin.security.verify'));
    }

    public function test_two_factor_can_be_disabled_with_a_valid_code_only(): void
    {
        $o = $this->owner();
        $o->forceFill(['two_factor_secret' => self::SECRET, 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($o)->post(route('admin.security.verify.submit'), ['code' => Totp::code(self::SECRET)])->assertRedirect();
        $this->actingAs($o)->post(route('admin.settings.security.disable'), ['code' => '000002'])->assertSessionHasErrors('code');
        $this->assertNotNull($o->fresh()->two_factor_confirmed_at);

        $this->actingAs($o)->post(route('admin.settings.security.disable'), ['code' => Totp::code(self::SECRET)])->assertSessionHasNoErrors();
        $this->assertNull($o->fresh()->two_factor_secret);
    }

    public function test_a_regular_user_cannot_reach_the_security_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.settings.security'))->assertNotFound();
    }

    /* ---------- Modèle de rappel ---------- */

    public function test_the_reminder_template_is_saved_and_rendered_with_placeholders(): void
    {
        $o = $this->owner();

        $this->actingAs($o)->post(route('admin.settings.notifications.update'), ['template' => 'Sak {nom}: {montan} {cur}'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Sak Marie: 500 HTG', Setting::renderReminder(['nom' => 'Marie', 'montan' => '500', 'cur' => 'HTG']));
        $this->assertSame('Bonjou {inkoni}', Setting::renderReminder(['nom' => 'X'], 'Bonjou {inkoni}'));
    }
}
