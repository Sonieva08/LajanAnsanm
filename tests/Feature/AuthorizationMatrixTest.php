<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function fixture(): array
    {
        $t = $this->start($this->makeTontine());
        $cycle = $this->openCycle($t);
        $member = $t->members()->first();
        $contribution = Contribution::create([
            'cycle_id' => $cycle->id, 'member_id' => $member->id,
            'amount' => 500, 'paid_at' => today(), 'payment_method' => 'cash',
        ]);

        return [$t, ['tontine' => $t, 'member' => $member, 'cycle' => $cycle,
            'contribution' => $contribution, 'round' => 1, 'direction' => 'up']];
    }

    // Toutes les routes liées à une tontine précise (hors liste, création, stockage)
    private function tontineRoutes()
    {
        return collect(Route::getRoutes()->getRoutes())->filter(
            fn ($r) => str_starts_with((string) $r->getName(), 'tontines.')
                && ! in_array($r->getName(), ['tontines.index', 'tontines.create', 'tontines.store'], true)
        );
    }

    private function urlFor($route, array $params): string
    {
        $needed = array_intersect_key($params, array_flip($route->parameterNames()));

        return route($route->getName(), $needed);
    }

    public function test_the_route_matrix_is_not_empty(): void
    {
        $this->assertGreaterThan(15, $this->tontineRoutes()->count());
    }

    public function test_guests_are_sent_to_login_on_every_route(): void
    {
        [, $params] = $this->fixture();
        auth()->logout();

        foreach ($this->tontineRoutes() as $route) {
            $res = $this->call($route->methods()[0], $this->urlFor($route, $params));

            $this->assertSame(302, $res->getStatusCode(), $route->getName());
            $this->assertSame(route('login'), $res->headers->get('Location'), $route->getName());
        }
    }

    public function test_another_user_gets_403_on_every_route(): void
    {
        [, $params] = $this->fixture();
        $intruder = User::factory()->create();

        foreach ($this->tontineRoutes() as $route) {
            $res = $this->actingAs($intruder)->call($route->methods()[0], $this->urlFor($route, $params));

            $this->assertSame(403, $res->getStatusCode(), $route->getName() . ' devrait être interdite');
        }
    }

    public function test_owner_can_open_every_page_without_error(): void
    {
        [$t, $params] = $this->fixture();

        foreach ($this->tontineRoutes()->filter(fn ($r) => in_array('GET', $r->methods())) as $route) {
            $res = $this->actingAs($t->admin)->get($this->urlFor($route, $params));

            $this->assertSame(200, $res->getStatusCode(), $route->getName());
        }
    }

    public function test_pages_do_not_crash_on_a_draft_tontine(): void
    {
        $t = $this->makeTontine();

        foreach (['tontines.show', 'tontines.edit', 'tontines.members.index', 'tontines.cycles.index',
                  'tontines.contributions.index', 'tontines.payouts.index', 'tontines.reports.index'] as $name) {
            $res = $this->actingAs($t->admin)->get(route($name, $t));

            $this->assertLessThan(500, $res->getStatusCode(), $name);
        }
    }

    public function test_dashboard_and_lists_work(): void
    {
        $t = $this->start($this->makeTontine());

        $this->actingAs($t->admin)->get('/dashboard')->assertOk()->assertSee('Sòl Test');
        $this->actingAs($t->admin)->get(route('tontines.index'))->assertOk()->assertSee('Sòl Test');
        $this->actingAs($t->admin)->get(route('tontines.create'))->assertOk();
    }

    public function test_a_user_only_sees_their_own_tontines(): void
    {
        $mine = $this->makeTontine(null, 3, ['name' => 'Ma tontine']);
        $other = $this->makeTontine(null, 3, ['name' => 'Tontine voisine']);

        $this->actingAs($mine->admin)->get(route('tontines.index'))
            ->assertSee('Ma tontine')->assertDontSee('Tontine voisine');
        $this->actingAs($mine->admin)->get('/dashboard')
            ->assertSee('Ma tontine')->assertDontSee('Tontine voisine');
    }
}
