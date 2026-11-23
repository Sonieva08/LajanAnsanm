<?php

namespace Tests\Feature;

use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileStitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_csv_export_contains_only_the_owners_members(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $t = Tontine::create(['admin_id' => $me->id, 'name' => 'Tontine mwen', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => 'draft']);
        $t->members()->create([
            'name' => 'Marie', 'phone' => '+509 4700 1111', 'email' => 'marie@example.com',
            'position' => 1, 'is_active' => true,
        ]);
        Tontine::create(['admin_id' => $other->id, 'name' => 'Tontine lòt moun', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => 'draft']);

        $res = $this->actingAs($me)->get(route('account.export', ['format' => 'csv']))->assertOk();

        $this->assertStringStartsWith('text/csv', (string) $res->headers->get('Content-Type'));
        $this->assertStringContainsString('Tontine mwen', $res->getContent());
        $this->assertStringContainsString('Marie', $res->getContent());
        $this->assertStringContainsString('marie@example.com', $res->getContent());
        $this->assertStringNotContainsString('Tontine lòt moun', $res->getContent());
    }

    public function test_spreadsheet_formulas_are_neutralised_in_the_csv(): void
    {
        $me = User::factory()->create();
        $t = Tontine::create(['admin_id' => $me->id, 'name' => '=HYPERLINK("http://x")', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => 'draft']);

        $csv = $this->actingAs($me)->get(route('account.export', ['format' => 'csv']))->getContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
    }

    public function test_the_page_links_both_exports_and_the_blocked_delete_state(): void
    {
        $u = User::factory()->create();
        Tontine::create(['admin_id' => $u->id, 'name' => 'Sòl aktif', 'amount_per_period' => 500,
            'period_type' => 'weekly', 'start_date' => today(), 'status' => 'active']);

        $this->actingAs($u)->get(route('profile.edit'))->assertOk()
            ->assertSee(route('account.export', ['format' => 'csv']), false)
            ->assertSee('Ou gen sòl an kou', false)
            ->assertSee('disabled', false);
    }

    public function test_pending_email_confirmation_can_be_resent_from_the_redesigned_profile(): void
    {
        $user = User::factory()->create(['pending_email' => 'new@example.com']);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee('new@example.com')
            ->assertSee(route('profile.email.resend'), false);
    }
}
