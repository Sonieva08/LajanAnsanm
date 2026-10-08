<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    private function maintenance(bool $on = true, ?string $message = 'Mizajou an kou', ?string $endsAt = null): void
    {
        Setting::set('maintenance', ['enabled' => $on, 'message' => $message, 'ends_at' => $endsAt]);
    }

    public function test_the_site_is_open_by_default(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_guests_see_the_maintenance_page_with_a_503(): void
    {
        $this->maintenance(true, 'Mizajou an kou', '2026-12-25T14:30');

        $res = $this->get('/');

        $res->assertStatus(503)->assertSee('Mizajou an kou')->assertSee('25/12/2026 14:30');
        $res->assertHeader('Retry-After');
    }

    public function test_the_default_message_is_used_when_none_is_given(): void
    {
        $this->maintenance(true, null);

        $this->get('/')->assertStatus(503)->assertSee('Nou ap fè antretyen')->assertSee('LajanAnsanm ap amelyore');
    }

    public function test_regular_logged_in_users_are_blocked_too(): void
    {
        $this->maintenance();

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertStatus(503);
    }

    public function test_the_owner_keeps_full_access_and_sees_a_warning_banner(): void
    {
        $this->maintenance();
        $owner = $this->owner();

        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertSee(route('admin.settings'), false);
        $this->actingAs($owner)->get(route('admin.overview'))->assertOk();
    }

    public function test_the_login_page_stays_reachable_and_the_owner_can_sign_in(): void
    {
        $this->maintenance();
        $owner = $this->owner();

        $this->get('/login')->assertOk();
        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_api_clients_get_a_json_503(): void
    {
        $this->maintenance(true, 'Mizajou');

        $this->getJson('/dashboard')->assertStatus(503)->assertJson(['message' => 'Mizajou']);
    }

    public function test_the_owner_can_switch_it_on_and_off_from_the_console(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.settings.maintenance'), [
            'enabled' => 1, 'message' => 'Antretyen pwograme', 'ends_at' => '2026-12-25T14:30',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Setting::maintenance()['enabled']);
        $this->assertSame(1, ActivityLog::where('action', 'maintenance_on')->count());
        $this->actingAs($owner)->get(route('admin.settings'))->assertOk()->assertSee('Antretyen pwograme');

        $this->actingAs($owner)->post(route('admin.settings.maintenance'), ['message' => ''])->assertSessionHasNoErrors();

        $this->assertFalse(Setting::maintenance()['enabled']);
        $this->assertSame(1, ActivityLog::where('action', 'maintenance_off')->count());
    }

    public function test_saving_without_changing_the_state_does_not_spam_the_log(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.settings.maintenance'), ['enabled' => 1, 'message' => 'A']);
        $this->actingAs($owner)->post(route('admin.settings.maintenance'), ['enabled' => 1, 'message' => 'B']);

        $this->assertSame(1, ActivityLog::where('action', 'maintenance_on')->count());
    }

    public function test_the_change_takes_effect_immediately_despite_the_cache(): void
    {
        $this->assertSame(200, $this->get('/')->getStatusCode());   // met "off" en cache

        $owner = $this->owner();
        $this->actingAs($owner)->post(route('admin.settings.maintenance'), ['enabled' => 1]);

        $this->assertSame(503, $this->app['auth']->guard('web')->forgetUser() ? $this->get('/')->getStatusCode() : 0);
    }

    public function test_only_the_owner_can_change_the_setting(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.settings.maintenance'), ['enabled' => 1])->assertNotFound();

        $this->assertFalse(Setting::maintenance()['enabled']);
    }

    public function test_the_form_validates_its_input(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.settings.maintenance'), ['enabled' => 1, 'message' => str_repeat('a', 301)])
            ->assertSessionHasErrors('message');
        $this->actingAs($owner)->post(route('admin.settings.maintenance'), ['enabled' => 1, 'ends_at' => 'demen'])
            ->assertSessionHasErrors('ends_at');

        $this->assertFalse(Setting::maintenance()['enabled']);
    }

    public function test_a_missing_settings_table_never_takes_the_site_down(): void
    {
        Schema::drop('settings');
        Cache::flush();

        $this->get('/')->assertOk();
    }
}
