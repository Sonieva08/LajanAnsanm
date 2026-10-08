<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    public function test_every_error_view_renders_on_its_own(): void
    {
        foreach (['403', '404', '419', '429', '500', '503', '4xx', '5xx', 'maintenance'] as $name) {
            $this->view("errors.{$name}")->assertSee('LajanAnsanm');
        }

        foreach (['403', '404', '419', '429', '500'] as $code) {
            $this->view("errors.{$code}")->assertSee($code);
        }

        $this->view('errors.503')->assertSee('Nou ap fè antretyen');
    }

    public function test_unknown_urls_show_the_branded_404_page(): void
    {
        $this->get('/paj-ki-pa-egziste')->assertNotFound()->assertSee('404')->assertSee('LajanAnsanm');
    }

    public function test_forbidden_access_shows_the_branded_403_page(): void
    {
        $t = $this->makeTontine();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('tontines.show', $t))->assertForbidden()->assertSee('403');
    }

    public function test_the_admin_area_looks_like_a_normal_404_to_outsiders(): void
    {
        $this->get('/admin')->assertNotFound()->assertSee('404');
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertNotFound()->assertSee('404');
    }

    public function test_a_server_error_shows_a_friendly_page_without_leaking_details(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/boom-test', fn () => throw new \RuntimeException('secret-db-password-xyz'));

        $res = $this->get('/boom-test');

        $res->assertStatus(500)->assertSee('500');
        $res->assertDontSee('secret-db-password-xyz');
        $res->assertDontSee('RuntimeException');
        $res->assertDontSee('Illuminate');
    }

    public function test_an_unusual_status_code_uses_the_generic_page(): void
    {
        Route::middleware('web')->get('/teapot-test', fn () => abort(418));

        $this->get('/teapot-test')->assertStatus(418)->assertSee('418');
    }
}
