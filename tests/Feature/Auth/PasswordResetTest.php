<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_link_requests_are_rate_limited_by_ip(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => $user->email])->assertTooManyRequests();
    }

    public function test_reset_password_link_uses_the_configured_app_url_instead_of_the_request_host(): void
    {
        Notification::fake();
        config(['app.url' => 'https://canonical.example.test']);

        $user = User::factory()->create();

        $this->withHeader('X-Forwarded-Host', 'attacker.example.test')
            ->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            $this->assertStringStartsWith('https://canonical.example.test/reset-password/', $url);
            $this->assertStringNotContainsString('attacker.example.test', $url);

            return true;
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password1234',
                'password_confirmation' => 'password1234',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}
