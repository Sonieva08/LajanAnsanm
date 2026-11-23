<?php

namespace Tests\Feature;

use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function tontine(User $user, string $name, string $status = 'draft'): Tontine
    {
        return Tontine::create([
            'admin_id' => $user->id,
            'name' => $name,
            'amount_per_period' => 500,
            'period_type' => 'weekly',
            'start_date' => today(),
            'status' => $status,
        ]);
    }

    public function test_the_profile_page_offers_every_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee(route('profile.preferences'), false)
            ->assertSee(route('account.export'), false)
            ->assertSee(route('account.destroy'), false)
            ->assertSee(route('password.update'), false)
            ->assertSee(route('subscription'), false);
    }

    public function test_a_google_only_account_is_offered_a_password_instead_of_a_change_form(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee(route('password.set'), false)
            ->assertDontSee(route('password.update'), false);
    }

    public function test_the_export_contains_only_the_owners_own_data(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        $other = User::factory()->create(['email' => 'other@example.com']);
        $mine = $this->tontine($user, 'Tontine mwen');
        $mine->members()->create(['name' => 'Marie', 'phone' => '+509 4700 1111', 'position' => 1, 'is_active' => true]);
        $this->tontine($other, 'Tontine lòt moun');

        $response = $this->actingAs($user)->get(route('account.export'))->assertOk()->assertHeader('Content-Disposition');
        $response->assertJsonPath('account.email', 'me@example.com');

        $body = $response->getContent();
        $this->assertStringContainsString('Tontine mwen', $body);
        $this->assertStringContainsString('+509 4700 1111', $body);
        $this->assertStringNotContainsString('Tontine lòt moun', $body);
        $this->assertStringNotContainsString('other@example.com', $body);
    }

    public function test_deletion_is_refused_while_a_tontine_is_running(): void
    {
        $user = User::factory()->create();
        $this->tontine($user, 'Sòl aktif', 'active');

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password'])
            ->assertSessionHasErrors('account');

        $this->assertNotNull(User::find($user->id));
    }

    public function test_a_user_with_no_running_tontine_deletes_with_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password'])->assertRedirect('/');

        $this->assertNull(User::find($user->id));
    }

    public function test_a_google_only_user_must_type_efase_to_delete(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->delete(route('account.destroy'), ['confirm' => 'efase'])->assertSessionHasErrors('confirm');
        $this->assertNotNull(User::find($user->id));

        $this->actingAs($user)->delete(route('account.destroy'), ['confirm' => 'EFASE'])->assertRedirect('/');
        $this->assertNull(User::find($user->id));
    }

    public function test_phone_and_language_are_saved_and_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.preferences'), ['phone' => '+509 4700 1111', 'locale' => 'fr'])
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('fr', $user->locale);
        $this->assertSame('+509 4700 1111', $user->phone);

        $this->actingAs($user)->post(route('profile.preferences'), ['phone' => 'abc', 'locale' => 'fr'])
            ->assertSessionHasErrors('phone');
        $this->actingAs($user)->post(route('profile.preferences'), ['phone' => '', 'locale' => 'en'])
            ->assertSessionHasErrors('locale');
    }

    public function test_a_saved_account_language_is_used_without_a_session_override(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profil et paramètres');
    }
}
