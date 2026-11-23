<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    private function review(User $user, string $status = 'approved', int $rating = 5, string $comment = 'Aplikasyon sa a ede m anpil.'): Review
    {
        return Review::unguarded(fn () => Review::create([
            'user_id' => $user->id, 'rating' => $rating, 'comment' => $comment, 'status' => $status,
        ]));
    }

    /* ---------- Lecture publique ---------- */

    public function test_the_public_reviews_page_is_open_to_guests_and_shows_only_approved_reviews(): void
    {
        $this->review(User::factory()->create(['name' => 'Marie Joseph']), 'approved', 5, 'Mwen renmen li anpil.');
        $this->review(User::factory()->create(), 'pending', 1, 'Avi ki poko apwouve.');
        $this->review(User::factory()->create(), 'hidden', 1, 'Avi ki kache.');

        $this->get(route('reviews.index'))->assertOk()
            ->assertSee('Mwen renmen li anpil.')
            ->assertDontSee('Avi ki poko apwouve.')
            ->assertDontSee('Avi ki kache.');
    }

    public function test_only_the_first_name_and_an_initial_are_shown_never_the_email(): void
    {
        $this->review(User::factory()->create(['name' => 'Marie Anne Joseph', 'email' => 'marie.secret@example.com']));

        $this->get(route('reviews.index'))->assertOk()
            ->assertSee('Marie J.')
            ->assertDontSee('Anne')
            ->assertDontSee('Joseph')
            ->assertDontSee('marie.secret@example.com');
    }

    public function test_public_pages_use_the_available_brand_logo_and_keep_review_navigation_integrated(): void
    {
        $logo = asset('images/logo-icon-sm.png');

        $this->get(route('home'))->assertOk()
            ->assertSee($logo, false)
            ->assertSee(route('reviews.index'), false);

        $this->get(route('reviews.index'))->assertOk()
            ->assertSee($logo, false)
            ->assertSee(route('home'), false)
            ->assertSee(route('privacy'), false);
    }

    public function test_comments_are_escaped_so_html_cannot_be_injected(): void
    {
        $this->review(User::factory()->create(), 'approved', 4, '<script>alert(1)</script> bon sèvis');

        $this->get(route('reviews.index'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_signed_in_users_see_the_form_and_guests_see_a_login_prompt(): void
    {
        $this->get(route('reviews.index'))->assertOk()
            ->assertSee(route('login'), false)
            ->assertDontSee('name="comment"', false);

        $this->actingAs(User::factory()->create())->get(route('reviews.index'))->assertOk()
            ->assertSee('name="comment"', false)
            ->assertSee(route('reviews.store'), false)
            ->assertSee(asset('images/logo-icon-sm.png'), false);
    }

    /* ---------- Écriture ---------- */

    public function test_guests_cannot_post_a_review(): void
    {
        $this->post(route('reviews.store'), ['rating' => 5, 'comment' => 'Bon travay'])->assertRedirect(route('login'));
        $this->assertSame(0, Review::count());
    }

    public function test_a_new_review_waits_for_approval(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('reviews.store'), ['rating' => 4, 'comment' => 'Trè itil pou sòl mwen.'])
            ->assertRedirect(route('reviews.index'));

        $this->assertSame('pending', $user->fresh()->review->status);
        $this->get(route('reviews.index'))->assertDontSee('Trè itil pou sòl mwen.');
    }

    public function test_a_user_cannot_approve_their_own_review_by_sending_a_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('reviews.store'), [
            'rating' => 5, 'comment' => 'Bon travay', 'status' => 'approved', 'user_id' => 999,
        ]);

        $this->assertSame('pending', $user->fresh()->review->status);
        $this->assertSame($user->id, $user->fresh()->review->user_id);
    }

    public function test_editing_a_review_updates_it_and_sends_it_back_to_moderation(): void
    {
        $user = User::factory()->create();
        $this->review($user, 'approved', 5, 'Premye kòmantè a.');

        $this->actingAs($user)->post(route('reviews.store'), ['rating' => 3, 'comment' => 'Kòmantè modifye a.']);

        $this->assertSame(1, Review::count());
        $this->assertSame(3, $user->fresh()->review->rating);
        $this->assertSame('pending', $user->fresh()->review->status);
    }

    public function test_validation_rejects_bad_ratings_and_empty_or_too_long_comments(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('reviews.store'), ['rating' => 9, 'comment' => 'Bon travay'])->assertSessionHasErrors('rating');
        $this->actingAs($user)->post(route('reviews.store'), ['rating' => 5, 'comment' => ''])->assertSessionHasErrors('comment');
        $this->actingAs($user)->post(route('reviews.store'), ['rating' => 5, 'comment' => str_repeat('a', 501)])->assertSessionHasErrors('comment');

        $this->assertSame(0, Review::count());
    }

    public function test_a_user_can_delete_only_their_own_review(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->review($user);
        $this->review($other);

        $this->actingAs($user)->delete(route('reviews.destroy'))->assertRedirect(route('reviews.index'));

        $this->assertNull($user->fresh()->review);
        $this->assertNotNull($other->fresh()->review);
    }

    /* ---------- Accueil ---------- */

    public function test_the_landing_shows_real_reviews_and_the_average(): void
    {
        $this->review(User::factory()->create(['name' => 'Jean Pierre']), 'approved', 5, 'Sèvis ekselan.');
        $this->review(User::factory()->create(), 'approved', 4, 'Trè bon.');

        $this->get('/')->assertOk()
            ->assertSee('Sèvis ekselan.')
            ->assertSee('4.5')
            ->assertSee(route('reviews.index'), false);
    }

    public function test_the_landing_invents_nothing_when_there_are_no_reviews(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('reviews.index'), false)
            ->assertDontSee('sou 5');
    }

    /* ---------- Modération (propriétaire) ---------- */

    public function test_the_owner_can_approve_hide_and_delete_reviews(): void
    {
        $owner = $this->owner();
        $review = $this->review(User::factory()->create(), 'pending');

        $this->actingAs($owner)->post(route('admin.reviews.approve', $review))->assertRedirect();
        $this->assertSame('approved', $review->fresh()->status);

        $this->actingAs($owner)->post(route('admin.reviews.hide', $review))->assertRedirect();
        $this->assertSame('hidden', $review->fresh()->status);

        $this->actingAs($owner)->delete(route('admin.reviews.destroy', $review))->assertRedirect();
        $this->assertNull(Review::find($review->id));
    }

    public function test_regular_users_get_a_404_on_the_moderation_pages(): void
    {
        $user = User::factory()->create();
        $review = $this->review(User::factory()->create(), 'pending');

        $this->actingAs($user)->get(route('admin.reviews'))->assertNotFound();
        $this->actingAs($user)->post(route('admin.reviews.approve', $review))->assertNotFound();
        $this->assertSame('pending', $review->fresh()->status);
    }
}
