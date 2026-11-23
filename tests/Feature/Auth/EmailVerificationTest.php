<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_failed_verification_email_send_shows_a_safe_message(): void
    {
        $user = User::factory()->unverified()->create();

        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new TransportException('SMTP authentication failed'));
        Log::shouldReceive('warning')
            ->once()
            ->with('Email verification notification could not be sent.', [
                'user_id' => $user->id,
                'exception' => TransportException::class,
            ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-email-failed');
    }

    public function test_unverified_users_cannot_access_organizer_or_owner_routes(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->get(route('tontines.index'))
            ->assertRedirect(route('verification.notice'));

        $owner = User::factory()->unverified()->create(['is_owner' => true]);
        $this->actingAs($owner)->get(route('admin.overview'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_email_can_be_verified_with_the_emailed_code(): void
    {
        $user = User::factory()->unverified()->create();

        Notification::fake();
        Event::fake();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-code-sent');

        $code = null;
        Notification::assertSentTo($user, EmailVerificationCode::class, function (EmailVerificationCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        $response = $this->post(route('verification.code'), ['code' => $code]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_an_invalid_code(): void
    {
        $user = User::factory()->unverified()->create();

        Notification::fake();
        $this->actingAs($user)->post(route('verification.send'));

        $code = null;
        Notification::assertSentTo($user, EmailVerificationCode::class, function (EmailVerificationCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        $wrongCode = $code === '000000' ? '000001' : '000000';
        $this->post(route('verification.code'), ['code' => $wrongCode])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_code_expires(): void
    {
        $user = User::factory()->unverified()->create();

        Notification::fake();
        $this->actingAs($user)->post(route('verification.send'));

        $code = null;
        Notification::assertSentTo($user, EmailVerificationCode::class, function (EmailVerificationCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        $this->travel(11)->minutes();
        $this->post(route('verification.code'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
