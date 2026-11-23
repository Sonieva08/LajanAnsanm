<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function enable($t, array $extra = []): void
    {
        $this->actingAs($t->admin)->put(route('tontines.portal.update', $t), array_merge([
            'member_portal_enabled' => 1,
            'moncash_number' => '+509 3700 0000',
            'moncash_name' => 'Marie Organizatè',
            'moncash_qr' => UploadedFile::fake()->createWithContent(
                'moncash.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=', true)
            ),
            'bank_info' => "BUH\n123-456",
        ], $extra))->assertSessionHasNoErrors();
    }

    private function linkFor(Member $m): string
    {
        return $m->fresh()->portalUrl();
    }

    public function test_portal_is_closed_by_default_and_links_do_not_exist(): void
    {
        $t = $this->makeTontine(null, 3);

        $this->assertFalse($t->fresh()->member_portal_enabled);
        $this->assertNull($t->members()->first()->access_token_hash);
    }

    public function test_creator_enables_portal_saves_payment_info_and_members_get_links(): void
    {
        $t = $this->makeTontine(null, 3);
        $this->enable($t);

        $t->refresh();
        $this->assertTrue($t->member_portal_enabled);
        $this->assertSame('+509 3700 0000', $t->portalPayment()['moncash_number']);
        $this->assertSame(['moncash' => 'MonCash', 'bank' => 'Bank'], $t->portalPaymentMethods());
        $this->assertSame(3, $t->members()->whereNotNull('access_token_hash')->count());

        $this->actingAs($t->admin)->get(route('tontines.portal.edit', $t))->assertOk()->assertSee('Membre 1');
    }

    public function test_only_the_creator_can_manage_the_portal(): void
    {
        $t = $this->makeTontine(null, 2);
        $other = $this->premiumUser();

        $this->actingAs($other)->get(route('tontines.portal.edit', $t))->assertForbidden();
        $this->actingAs($other)->put(route('tontines.portal.update', $t), ['member_portal_enabled' => 1])->assertForbidden();
        $this->assertFalse($t->fresh()->member_portal_enabled);
    }

    public function test_member_link_opens_the_calendar(): void
    {
        $t = $this->makeTontine(null, 3);
        $this->enable($t);
        $t = $this->start($t);
        $m = $t->members()->where('position', 2)->first();

        $this->get($this->linkFor($m))->assertRedirect(route('portal.home'));

        $html = $this->get(route('portal.home'))
            ->assertOk()
            ->assertSee('Sòl Test')
            ->assertSee('Membre 1')
            ->assertSee('Membre 3')
            ->assertSee('Marie Organizatè')       // coordonnées de paiement affichées
            ->assertSee('images/moncash_1.png')
            ->assertSee('data-payment-info', false);

        $this->assertSame(
            $t->roundCycles()->count(),
            substr_count($html->getContent(), __('Benefisyè tou sa a'))
        );
        $this->assertSame(
            $t->activeMembersCount(),
            $t->roundCycles()->distinct('beneficiary_member_id')->count('beneficiary_member_id')
        );
    }

    public function test_member_never_sees_other_members_phones_or_payments(): void
    {
        $t = $this->makeTontine(null, 3);
        $this->enable($t);
        $t = $this->start($t);
        $cycle = $this->openCycle($t);
        $me = $t->members()->where('position', 2)->first();
        $other = $t->members()->where('position', 3)->first();
        $this->pay($t, $cycle, $other)->assertSessionHasNoErrors();

        $this->get($this->linkFor($me));
        $html = $this->get(route('portal.home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('37000003', $html);
        $this->assertStringNotContainsString('37000001', $html);
        // mon propre état : rien de payé, le tour ouvert est à payer
        $this->assertStringContainsString('500 HTG', $html);
        $this->assertStringContainsString('Pou peye kounye a', $html);
    }

    public function test_paid_member_sees_confirmation_instead_of_amount_due(): void
    {
        $t = $this->makeTontine(null, 3);
        $this->enable($t);
        $t = $this->start($t);
        $cycle = $this->openCycle($t);
        $me = $t->members()->where('position', 2)->first();
        $this->pay($t, $cycle, $me)->assertSessionHasNoErrors();

        $this->get($this->linkFor($me));
        $this->get(route('portal.home'))->assertOk()
            ->assertDontSee('data-payment-info', false)
            ->assertSee('Ou peye pou tou');
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->get('/m/'.str_repeat('a', 40))->assertNotFound();
        $this->get('/m/short')->assertNotFound();
        $this->get(route('portal.home'))->assertForbidden();
    }

    public function test_closing_the_portal_blocks_members_immediately(): void
    {
        $t = $this->makeTontine(null, 2);
        $this->enable($t);
        $m = $t->members()->first();
        $link = $this->linkFor($m);

        $this->get($link)->assertRedirect();
        $this->get(route('portal.home'))->assertOk();

        $this->enable($t, ['member_portal_enabled' => 0]);

        $this->get(route('portal.home'))->assertForbidden();
        $this->get($link)->assertNotFound();
    }

    public function test_deactivated_member_loses_access(): void
    {
        $t = $this->makeTontine(null, 3);
        $this->enable($t);
        $m = $t->members()->where('position', 3)->first();
        $link = $this->linkFor($m);

        $this->get($link)->assertRedirect();
        $m->update(['is_active' => false]);

        $this->get(route('portal.home'))->assertForbidden();
        $this->get($link)->assertNotFound();
    }

    public function test_renewing_a_link_kills_the_old_link_and_old_sessions(): void
    {
        $t = $this->makeTontine(null, 2);
        $this->enable($t);
        $m = $t->members()->first();
        $old = $this->linkFor($m);

        $this->get($old)->assertRedirect();
        $this->get(route('portal.home'))->assertOk();

        $this->actingAs($t->admin)->post(route('tontines.portal.link', [$t, $m]))->assertSessionHasNoErrors();
        $new = $this->linkFor($m);

        $this->assertNotSame($old, $new);
        $this->get($old)->assertNotFound();
        $this->get(route('portal.home'))->assertForbidden();   // l'ancienne session ne marche plus
        $this->get($new)->assertRedirect(route('portal.home'));
        $this->get(route('portal.home'))->assertOk();
    }

    public function test_token_is_stored_encrypted_and_hidden(): void
    {
        $t = $this->makeTontine(null, 2);
        $this->enable($t);
        $m = $t->members()->first();
        $plain = $m->fresh()->access_token;

        $this->assertSame(40, strlen($plain));
        $this->assertNotSame($plain, $m->getRawOriginal('access_token'));
        $this->assertStringNotContainsString($plain, json_encode($m->fresh()->toArray()));
    }

    public function test_a_member_of_another_tontine_cannot_be_reached_through_this_one(): void
    {
        $a = $this->makeTontine(null, 2);
        $b = $this->makeTontine(null, 2);
        $this->enable($a);
        $this->enable($b);
        $bm = $b->members()->first();

        // le créateur de A ne peut pas renouveler le lien d'un membre de B
        $this->actingAs($a->admin)->post(route('tontines.portal.link', [$a, $bm]))->assertNotFound();
    }

    public function test_logout_clears_the_session(): void
    {
        $t = $this->makeTontine(null, 2);
        $this->enable($t);
        $this->get($this->linkFor($t->members()->first()));

        $this->post(route('portal.logout'))->assertRedirect('/');
        $this->get(route('portal.home'))->assertForbidden();
    }

    public function test_whatsapp_number_gets_haiti_prefix_when_missing(): void
    {
        $m = new Member(['phone' => '3700 0001']);
        $this->assertSame('50937000001', $m->whatsappNumber());

        $m = new Member(['phone' => '+509 3700 0001']);
        $this->assertSame('50937000001', $m->whatsappNumber());

        $this->assertNull((new Member(['phone' => null]))->whatsappNumber());
    }
}
