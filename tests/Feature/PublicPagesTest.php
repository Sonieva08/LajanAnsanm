<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $u = User::factory()->create(['name' => 'Chef Pwopriyetè']);
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    public function test_no_support_link_is_shown_until_a_number_is_configured(): void
    {
        config(['lajanansanm.support_whatsapp' => null]);
        $this->view('errors.500')->assertDontSee('wa.me');
        $this->view('errors.maintenance')->assertDontSee('wa.me');

        config(['lajanansanm.support_whatsapp' => '+509 3600-0000']);
        $this->view('errors.500')->assertSee('https://wa.me/50936000000', false);
        $this->view('errors.maintenance')->assertSee('https://wa.me/50936000000', false);
    }

    public function test_the_https_badge_is_only_shown_on_a_secure_connection(): void
    {
        $this->get('/paj-ki-pa-egziste')->assertNotFound()->assertDontSee('HTTPS');

        $this->get('https://127.0.0.1/paj-ki-pa-egziste')->assertNotFound()->assertSee('HTTPS');
    }

    public function test_the_429_page_uses_the_real_retry_delay(): void
    {
        $this->view('errors.429', ['exception' => new TooManyRequestsHttpException(45)])->assertSee('45s');
        $this->view('errors.429')->assertSee('60s');
    }

    public function test_the_500_page_gives_a_timestamp_reference_and_makes_no_false_promise(): void
    {
        $this->view('errors.500')->assertSee('SRV-')->assertDontSee('Ekip la ap')->assertDontSee('24/7');
    }

    public function test_the_maintenance_page_refreshes_itself_and_makes_no_security_claims(): void
    {
        $view = $this->view('errors.maintenance');

        $view->assertSee('http-equiv="refresh"', false);
        $view->assertDontSee('256-bit');
        $view->assertDontSee('Kotizasyon yo ap trete');
    }

    public function test_the_retry_buttons_never_resubmit_a_form(): void
    {
        foreach (['419', '500', 'maintenance'] as $name) {
            $this->view("errors.{$name}")->assertDontSee('location.reload()', false);
        }
    }

    public function test_the_settings_page_remembers_who_changed_it(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('admin.settings'))->assertOk()->assertSee('Poko gen modifikasyon');

        $this->actingAs($owner)->post(route('admin.settings.maintenance'), ['enabled' => 1, 'message' => 'Mizajou'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Chef Pwopriyetè', Setting::maintenance()['updated_by']);
        $this->actingAs($owner)->get(route('admin.settings'))->assertOk()->assertSee('Chef Pwopriyetè');
    }

    public function test_the_unbuilt_settings_tabs_are_not_clickable(): void
    {
        $this->actingAs($this->owner())->get(route('admin.settings'))->assertOk()->assertSee('Byento');
    }

    public function test_the_empty_state_variants_render(): void
    {
        foreach (['tontine', 'members', 'reports', 'search'] as $art) {
            $this->blade('<x-empty-state :art="$art" title="Vid" text="Tèks" href="/x" action="Ale" />', ['art' => $art])
                ->assertSee('data-empty-state', false)->assertSee('Ale');
        }

        $this->blade('<x-empty-state compact icon="history" title="Vid" href="/x" action="Reyajiste" />')
            ->assertSee('data-empty-state', false)->assertSee('Reyajiste');
    }
}
