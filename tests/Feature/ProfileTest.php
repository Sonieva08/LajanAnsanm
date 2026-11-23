<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use App\Notifications\EmailChangeAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_name_can_be_updated_without_changing_the_email(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->pending_email);
    }

    public function test_changing_email_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'new@example.com',
                'current_password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/profile');

        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_email_change_waits_for_signed_confirmation_and_notifies_old_address(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $oldEmail = $user->email;

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => 'new@example.com',
            'current_password' => 'password',
        ])->assertRedirect('/profile')->assertSessionHas('status', 'email-change-pending');

        $user->refresh();
        $this->assertSame($oldEmail, $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertSentOnDemand(ConfirmEmailChange::class);
        Notification::assertSentOnDemand(
            ConfirmEmailChange::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'new@example.com'
        );
        $this->actingAs($user)
            ->post(route('profile.email.resend'))
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'email-change-link-sent');

        $url = URL::temporarySignedRoute(
            'profile.email.confirm',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => sha1('new@example.com')]
        );

        $this->get($url)->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertSentOnDemand(
            EmailChangeAlert::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $oldEmail
        );
    }

    public function test_email_change_rejects_an_address_in_use_or_pending_confirmation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $other->forceFill(['pending_email' => 'reserved@example.com'])->save();

        foreach ([$other->email, 'reserved@example.com'] as $email) {
            $this->actingAs($user)->patch('/profile', [
                'name' => $user->name,
                'email' => $email,
                'current_password' => 'password',
            ])->assertSessionHasErrors('email');
        }
    }

    public function test_email_change_confirmation_requires_a_valid_signature_and_matching_pending_address(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['pending_email' => 'new@example.com'])->save();
        $url = URL::temporarySignedRoute(
            'profile.email.confirm',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => sha1('another@example.com')]
        );

        $this->get(str_replace('signature=', 'signature=invalid', $url))->assertForbidden();
        $this->get($url)->assertForbidden();
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
