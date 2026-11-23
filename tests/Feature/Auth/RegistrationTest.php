<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
        Notification::assertSentTo(
            User::where('email', 'test@example.com')->firstOrFail(),
            EmailVerificationCode::class
        );
    }

    public function test_production_passwords_require_length_case_and_numbers(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production', 'app.force_https' => false]);
        $this->assertTrue(app()->isProduction());
        $this->assertFalse(Validator::make(
            ['password' => 'alllowercase'],
            ['password' => Password::defaults()]
        )->passes());

        $this->withoutMiddleware(\App\Http\Middleware\SecureResponse::class);
    }
}
