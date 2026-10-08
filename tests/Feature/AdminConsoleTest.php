<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function owner(): User
    {
        $u = User::factory()->create(['name' => 'Le Propriétaire']);
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    private function adminRoutes()
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with((string) $r->getName(), 'admin.'));
    }

    private function urlFor($route, User $target): string
    {
        $params = [
            'user' => $target,
            'plan' => \App\Models\Plan::where('slug', 'pro')->firstOrFail(),
            'subscriptionRequest' => \App\Models\SubscriptionRequest::firstOrCreate(
                ['reference_code' => 'LA-TEST-001'],
                ['user_id' => $target->id, 'plan_id' => \App\Models\Plan::where('slug', 'pro')->value('id'), 'billing_period' => 'monthly',
                 'amount' => 100, 'payment_method' => 'moncash', 'payer_phone' => '+50911111111', 'transaction_ref' => 'T1']
            ),
        ];

        return route($route->getName(), array_intersect_key($params, array_flip($route->parameterNames())));
    }

    /* ---------- Accès ---------- */

    public function test_the_admin_routes_exist(): void
    {
        $this->assertGreaterThanOrEqual(4, $this->adminRoutes()->count());
    }

    public function test_guests_get_a_404_not_a_login_redirect(): void
    {
        $target = User::factory()->create();

        foreach ($this->adminRoutes() as $route) {
            $res = $this->call($route->methods()[0], $this->urlFor($route, $target));
            $this->assertSame(404, $res->getStatusCode(), $route->getName());
        }
    }

    public function test_regular_users_get_a_404_on_every_admin_route(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        foreach ($this->adminRoutes() as $route) {
            $res = $this->actingAs($user)->call($route->methods()[0], $this->urlFor($route, $target));
            $this->assertSame(404, $res->getStatusCode(), $route->getName());
        }

        $this->assertNull($target->fresh()->disabled_at, "un utilisateur normal ne doit rien pouvoir désactiver");
    }

    public function test_the_owner_can_open_every_admin_page(): void
    {
        $owner = $this->owner();
        $target = User::factory()->create();

        foreach ($this->adminRoutes()->filter(fn ($r) => in_array('GET', $r->methods())) as $route) {
            $this->assertSame(200, $this->actingAs($owner)->get($this->urlFor($route, $target))->getStatusCode(), $route->getName());
        }

        foreach ([7, 30, 90] as $days) {
            $this->actingAs($owner)->get(route('admin.overview', ['days' => $days]))->assertOk();
        }
        $this->actingAs($owner)->get(route('admin.users', ['user' => $target->id]))->assertOk()->assertSee($target->email);
    }

    public function test_the_owner_role_cannot_be_forced_through_mass_assignment_or_registration(): void
    {
        $u = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'is_owner' => true]);
        $this->assertFalse((bool) $u->fresh()->is_owner);

        $this->post('/register', [
            'name' => 'Pirate', 'email' => 'pirate@example.com',
            'password' => 'password', 'password_confirmation' => 'password', 'is_owner' => 1,
        ]);
        $this->assertFalse((bool) User::where('email', 'pirate@example.com')->firstOrFail()->is_owner);
    }

    public function test_make_owner_command_grants_and_revokes(): void
    {
        $u = User::factory()->create(['email' => 'boss@example.com']);

        $this->artisan('lajan:make-owner', ['email' => 'boss@example.com'])->assertExitCode(0);
        $this->assertTrue($u->fresh()->isOwner());

        $this->artisan('lajan:make-owner', ['email' => 'boss@example.com', '--revoke' => true])->assertExitCode(0);
        $this->assertFalse($u->fresh()->isOwner());

        $this->artisan('lajan:make-owner', ['email' => 'nobody@example.com'])->assertExitCode(1);
    }

    /* ---------- Comptes ---------- */

    public function test_the_owner_can_disable_and_enable_an_account(): void
    {
        $owner = $this->owner();
        $u = User::factory()->create();

        $this->actingAs($owner)->post(route('admin.users.toggle', $u))->assertSessionHasNoErrors();
        $this->assertNotNull($u->fresh()->disabled_at);
        $this->assertSame(1, ActivityLog::where('action', 'account_disabled')->count());

        $this->actingAs($owner)->post(route('admin.users.toggle', $u));
        $this->assertNull($u->fresh()->disabled_at);
        $this->assertSame(1, ActivityLog::where('action', 'account_enabled')->count());
    }

    public function test_the_owner_cannot_disable_themselves_or_another_owner(): void
    {
        $owner = $this->owner();
        $other = $this->owner();

        $this->actingAs($owner)->post(route('admin.users.toggle', $owner))->assertForbidden();
        $this->actingAs($owner)->post(route('admin.users.toggle', $other))->assertForbidden();

        $this->assertNull($owner->fresh()->disabled_at);
        $this->assertNull($other->fresh()->disabled_at);
    }

    public function test_a_disabled_user_is_logged_out_on_the_next_request(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['disabled_at' => now()])->save();

        $this->actingAs($u)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_last_seen_is_tracked_but_not_written_on_every_request(): void
    {
        $u = User::factory()->create();

        Carbon::setTestNow('2026-10-10 10:00:00');
        $this->actingAs($u)->get('/dashboard');
        $this->assertSame('2026-10-10 10:00:00', $u->fresh()->last_seen_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-10-10 10:02:00');
        $this->actingAs($u->fresh())->get('/dashboard');
        $this->assertSame('2026-10-10 10:00:00', $u->fresh()->last_seen_at->format('Y-m-d H:i:s'), 'moins de 5 minutes : pas de réécriture');

        Carbon::setTestNow('2026-10-10 10:10:00');
        $this->actingAs($u->fresh())->get('/dashboard');
        $this->assertSame('2026-10-10 10:10:00', $u->fresh()->last_seen_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    /* ---------- Journal d'activité ---------- */

    public function test_key_actions_are_logged(): void
    {
        $t = $this->makeTontine(null, 3, ['organizer_fee_percent' => 10]);
        $this->assertSame(1, ActivityLog::where('action', 'tontine_created')->count());

        $t = $this->start($t);
        $this->assertSame(1, ActivityLog::where('action', 'calendar_generated')->count());

        $cycle = $this->openCycle($t);
        $this->payAll($t, $cycle);
        $this->assertSame(3, ActivityLog::where('action', 'contribution_recorded')->count());

        $this->payout($t, $cycle);
        $log = ActivityLog::where('action', 'payout_made')->firstOrFail();
        $this->assertEquals(1350, $log->meta['net']);
        $this->assertEquals(150, $log->meta['fee']);
        $this->assertSame($t->admin->id, $log->user_id);
        $this->assertSame($t->id, $log->tontine_id);
    }

    public function test_completion_restart_and_cancellations_are_logged(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 2)));
        $this->assertSame(1, ActivityLog::where('action', 'tontine_completed')->count());

        $this->actingAs($t->admin)->post(route('tontines.cycles.restart', $t), ['start_date' => today()->format('Y-m-d')]);
        $this->assertSame(1, ActivityLog::where('action', 'round_restarted')->count());

        $t2 = $this->start($this->makeTontine(null, 2));
        $this->actingAs($t2->admin)->delete(route('tontines.cycles.reset', $t2));
        $this->assertSame(1, ActivityLog::where('action', 'calendar_cancelled')->count());
    }

    public function test_login_and_registration_are_logged(): void
    {
        $this->post('/register', ['name' => 'Nouvelle', 'email' => 'n@example.com', 'password' => 'password', 'password_confirmation' => 'password']);
        $this->assertSame(1, ActivityLog::where('action', 'register')->count());

        auth()->logout();
        $u = User::factory()->create();
        $this->post('/login', ['email' => $u->email, 'password' => 'password']);
        $this->assertSame(1, ActivityLog::where('action', 'login')->where('user_id', $u->id)->count());
    }

    public function test_the_log_never_contains_member_names_or_phones(): void
    {
        $t = $this->complete($this->start($this->makeTontine(null, 3)));
        $this->actingAs($t->admin)->post(route('tontines.cycles.restart', $t), ['start_date' => today()->format('Y-m-d')]);

        $dump = ActivityLog::all()->map(fn ($l) => json_encode($l->meta))->implode(' ');

        $this->assertStringNotContainsString('Membre', $dump);
        $this->assertStringNotContainsString('+509', $dump);
    }

    public function test_deleting_an_account_keeps_the_log_entries(): void
    {
        $t = $this->makeTontine();
        $admin = $t->admin;

        $admin->delete();

        $this->assertGreaterThan(0, ActivityLog::count());
        $this->assertNull(ActivityLog::first()->user_id);
    }

    public function test_activity_page_filters_and_exports_csv(): void
    {
        $owner = $this->owner();
        $t = $this->start($this->makeTontine());
        $this->payAll($t, $this->openCycle($t));

        $res = $this->actingAs($owner)->get(route('admin.activity', ['action' => 'contribution_recorded']))->assertOk();
        $this->assertSame(3, $res->viewData('logs')->total());

        $csv = $this->actingAs($owner)->get(route('admin.activity', ['format' => 'csv']));
        $csv->assertOk()->assertDownload();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv->streamedContent());

        // Une date invalide ou une action inconnue ne plante pas
        $this->actingAs($owner)->get(route('admin.activity', ['from' => 'pas-une-date', 'action' => 'hack']))->assertOk();
    }

    /* ---------- Indicateurs ---------- */

    public function test_overview_counts_only_regular_accounts(): void
    {
        $owner = $this->owner();
        $t = $this->start($this->makeTontine(null, 3));
        $this->pay($t, $this->openCycle($t), $t->members()->first());

        $res = $this->actingAs($owner)->get(route('admin.overview'))->assertOk();
        $kpi = $res->viewData('kpi');

        $this->assertSame(1, $kpi['total_users'], 'le propriétaire ne compte pas');
        $this->assertSame(1, $kpi['active_tontines']);
        $this->assertEquals(500, $kpi['volume_month']);
    }

    public function test_the_activation_funnel_is_correct_and_never_increases(): void
    {
        $owner = $this->owner();
        User::factory()->create();                                    // inscrit seulement
        $draft = $this->makeTontine(null, 1);                         // a créé une tontine, 1 seul membre
        $started = $this->start($this->makeTontine(null, 3));        // calendrier lancé
        $this->pay($started, $this->openCycle($started), $started->members()->first());
        $this->complete($this->start($this->makeTontine(null, 2)));   // a terminé un cycle

        $funnel = collect($this->actingAs($owner)->get(route('admin.overview'))->viewData('funnel'));
        $counts = $funnel->pluck('count')->all();

        // 4 comptes ; 3 ont créé une tontine ; 2 ont 2+ membres ; 2 ont un calendrier ; 2 ont un paiement ; 1 a terminé un cycle
        $this->assertSame([4, 3, 2, 2, 2, 1], $counts);
        $this->assertSame($counts, collect($counts)->sortDesc()->values()->all(), "l'entonnoir doit être décroissant");
    }

    public function test_the_late_rate_is_computed_on_open_cycles(): void
    {
        $owner = $this->owner();
        $t = $this->start($this->makeTontine(null, 3, ['start_date' => today()->subDays(10)]));
        $this->pay($t, $this->openCycle($t), $t->members()->first()); // 1 payé sur 3, tour en retard de 10 jours

        $late = $this->actingAs($owner)->get(route('admin.overview'))->viewData('late');

        $this->assertSame(2, $late['unpaid']);
        $this->assertSame(3, $late['expected']);
        $this->assertEqualsWithDelta(66.7, $late['rate'], 0.1);
        $this->assertSame(2, $late['buckets']['8+']);
    }

    public function test_the_users_page_searches_and_filters(): void
    {
        $owner = $this->owner();
        User::factory()->create(['name' => 'Alice Test', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob Autre', 'email' => 'bob@example.com'])
            ->forceFill(['disabled_at' => now()])->save();

        $this->actingAs($owner)->get(route('admin.users', ['q' => 'alice']))->assertSee('alice@example.com')->assertDontSee('bob@example.com');
        $this->actingAs($owner)->get(route('admin.users', ['status' => 'disabled']))->assertSee('bob@example.com')->assertDontSee('alice@example.com');
        $this->actingAs($owner)->get(route('admin.users'))->assertDontSee($owner->email);
    }

    public function test_the_owner_menu_link_is_visible_only_to_the_owner(): void
    {
        $owner = $this->owner();
        $user = User::factory()->create();

        $this->actingAs($owner)->get('/dashboard')->assertSee(route('admin.overview'), false);
        $this->actingAs($user)->get('/dashboard')->assertDontSee(route('admin.overview'), false);
    }
}
