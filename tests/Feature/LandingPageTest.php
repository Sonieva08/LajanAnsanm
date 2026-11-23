<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_landing_page_with_both_entry_points(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false)
            ->assertSee(route('terms'), false)
            ->assertSee('Kondisyon Sèvis');
    }

    public function test_signed_in_users_go_straight_to_their_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_the_landing_makes_no_invented_claims(): void
    {
        $html = $this->get('/')->getContent();

        foreach (['24/7', 'Kondisyon Itilizasyon', 'Kalkilatris', 'Sòl Dijital', 'Pòtoprens'] as $claim) {
            $this->assertStringNotContainsString($claim, $html);
        }
    }

    public function test_the_whatsapp_link_appears_only_when_configured(): void
    {
        config(['lajanansanm.support_whatsapp' => null]);
        $this->get('/')->assertDontSee('wa.me');

        config(['lajanansanm.support_whatsapp' => '+509 3600-0000']);
        $this->get('/')->assertSee('https://wa.me/50936000000', false);
    }

    public function test_the_login_page_has_email_password_and_the_google_option(): void
    {
        config(['services.google.client_id' => 'test-id']);

        $this->get(route('login'))->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee(route('google.redirect'), false)
            ->assertSee(route('register'), false);
    }

    public function test_terms_page_is_available_and_registration_links_to_it(): void
    {
        $this->withSession(['locale' => 'fr'])->get(route('terms'))->assertOk()
            ->assertSee('CONDITIONS GÉNÉRALES D’UTILISATION', false)
            ->assertSee('Les contributions destinées aux tontines sont transférées directement entre les personnes concernées', false);

        $this->get(route('register'))->assertOk()->assertSee(route('terms'), false);
    }
}
