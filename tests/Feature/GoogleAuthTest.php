<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-id', 'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback']);
    }

    private function googleUser(string $id, string $email, bool $verified = true, string $name = 'Marie Test')
    {
        $social = Mockery::mock();
        $social->shouldReceive('getId')->andReturn($id);
        $social->shouldReceive('getEmail')->andReturn($email);
        $social->shouldReceive('getName')->andReturn($name);
        $social->shouldReceive('getRaw')->andReturn(['email_verified' => $verified]);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($social);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    public function test_a_new_google_user_gets_an_account_and_is_signed_in(): void
    {
        $this->googleUser('g-1', 'marie@example.com');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $u = User::where('email', 'marie@example.com')->firstOrFail();
        $this->assertSame('g-1', $u->google_id);
        $this->assertNull($u->password);
        $this->assertNotNull($u->email_verified_at);
    }

    public function test_an_existing_account_with_the_same_verified_email_is_linked_and_keeps_its_password(): void
    {
        $existing = User::factory()->create(['email' => 'amina@example.com']); // mot de passe « password »
        $this->googleUser('g-2', 'amina@example.com');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $existing->refresh();
        $this->assertSame('g-2', $existing->google_id);
        $this->assertTrue(Hash::check('password', $existing->password));
        $this->assertSame(1, User::where('email', 'amina@example.com')->count());
    }

    public function test_an_unverified_google_email_is_refused(): void
    {
        $this->googleUser('g-3', 'pirate@example.com', false);

        $this->get(route('google.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(0, User::where('email', 'pirate@example.com')->count());
    }

    public function test_an_account_already_linked_to_another_google_identity_is_refused(): void
    {
        User::factory()->create(['email' => 'lien@example.com', 'google_id' => 'g-OLD']);
        $this->googleUser('g-NEW', 'lien@example.com');

        $this->get(route('google.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame('g-OLD', User::where('email', 'lien@example.com')->value('google_id'));
    }

    public function test_a_disabled_account_cannot_sign_in_with_google(): void
    {
        User::factory()->create(['email' => 'bloke@example.com', 'google_id' => 'g-4', 'disabled_at' => now()]);
        $this->googleUser('g-4', 'bloke@example.com');

        $this->get(route('google.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_the_button_appears_only_when_google_is_configured(): void
    {
        config(['services.google.client_id' => null]);
        $this->get('/login')->assertOk()->assertDontSee('/auth/google', false);

        config(['services.google.client_id' => 'test-id']);
        $this->get('/login')->assertOk()->assertSee('/auth/google', false);
    }

    public function test_the_google_redirect_is_refused_cleanly_when_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get(route('google.redirect'))->assertRedirect(route('login'));
    }

    public function test_a_google_only_account_can_set_a_password_without_a_current_one(): void
    {
        $u = User::factory()->create(['password' => null]);

        $this->actingAs($u)->post(route('password.set.update'), ['password' => 'NouvoModpas123!', 'password_confirmation' => 'NouvoModpas123!'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NouvoModpas123!', $u->fresh()->password));
    }

    public function test_an_account_with_a_password_must_confirm_the_current_one(): void
    {
        $u = User::factory()->create(); // mot de passe « password »

        $this->actingAs($u)->post(route('password.set.update'), ['password' => 'NouvoModpas123!', 'password_confirmation' => 'NouvoModpas123!'])
            ->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $u->fresh()->password));
    }

    public function test_password_validation_messages_are_translated_in_haitian_creole(): void
    {
        $u = User::factory()->create(['password' => null]);

        $this->actingAs($u)->withSession(['locale' => 'ht'])
            ->post(route('password.set.update'), ['password' => 'abc', 'password_confirmation' => 'xyz'])
            ->assertSessionHasErrors('password');

        $messages = session('errors')->getBag('default')->get('password');

        $this->assertContains('Konfimasyon modpas la pa koresponn.', $messages);
        $this->assertContains('Modpas dwe gen omwen 10 karaktè.', $messages);
        $this->assertNotContains('validation.confirmed', $messages);
        $this->assertNotContains('validation.min.string', $messages);
    }
}
