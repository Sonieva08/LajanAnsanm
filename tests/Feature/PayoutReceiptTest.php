<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Member;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class PayoutReceiptTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    private function setupPortal(): array
    {
        $t = $this->makeTontine(null, 3);
        $this->actingAs($t->admin)->put(route('tontines.portal.update', $t), ['member_portal_enabled' => 1, 'moncash_number' => '+509 3700 0000'])
            ->assertSessionHasNoErrors();
        $t = $this->start($t);

        return [$t, $this->openCycle($t)];
    }

    private function enter(Member $m): void
    {
        $this->get($m->fresh()->portalUrl())->assertRedirect(route('portal.home'));
    }

    private function saveDestination(array $extra = [])
    {
        return $this->post(route('portal.payout.destination'), array_merge([
            'payout_method' => 'moncash', 'payout_account' => '+509 3811 2233', 'payout_name' => 'Marie J.',
        ], $extra));
    }

    private function receiptImage(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'transfer.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l2cAAAAASUVORK5CYII=')
        );
    }

    // Le bénéficiaire du tour ouvert saisit où il veut recevoir, puis le créateur verse. Renvoie [tontine, tour, bénéficiaire, versement].
    private function paidOut(): array
    {
        [$t, $cycle] = $this->setupPortal();
        $ben = $cycle->beneficiary;
        $this->enter($ben);
        $this->saveDestination()->assertSessionHasNoErrors();
        $this->payAll($t, $cycle);
        $this->payout($t, $cycle)->assertSessionHasNoErrors();

        return [$t, $cycle, $ben, Payout::firstOrFail()];
    }

    public function test_member_saves_how_to_receive_and_creator_sees_it_before_paying(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $ben = $cycle->beneficiary;
        $this->enter($ben);

        $this->get(route('portal.home'))->assertSee('data-destination-form', false);
        $this->saveDestination()->assertSessionHasNoErrors();

        $this->assertSame('moncash', $ben->fresh()->payout_method);
        $this->actingAs($t->admin)->get(route('tontines.payouts.index', $t))->assertOk()
            ->assertSee('data-destination', false)->assertSee('+509 3811 2233');
    }

    public function test_destination_is_validated(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $this->enter($cycle->beneficiary);

        $this->saveDestination(['payout_method' => 'paypal'])->assertSessionHasErrors('payout_method');
        $this->saveDestination(['payout_account' => ''])->assertSessionHasErrors('payout_account');       // compte obligatoire hors cash
        $this->saveDestination(['payout_method' => 'cash', 'payout_account' => '', 'payout_name' => 'Pa nesesè'])
            ->assertSessionHasNoErrors();
        $this->assertNull($cycle->beneficiary->fresh()->payout_account);
        $this->assertNull($cycle->beneficiary->fresh()->payout_name);
        $response = $this->get(route('portal.home'))->assertOk();
        $this->assertMatchesRegularExpression('/data-payout-name-wrap\s+hidden/', $response->getContent());
    }

    public function test_cash_payout_does_not_show_or_require_transfer_details(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $beneficiary = $cycle->beneficiary;
        $this->enter($beneficiary);
        $this->saveDestination([
            'payout_method' => 'cash',
            'payout_account' => '',
            'payout_name' => '',
        ])->assertSessionHasNoErrors();
        $this->payAll($t, $cycle);

        $response = $this->actingAs($t->admin)->get(route('tontines.payouts.index', $t))->assertOk();
        $response->assertSee('Benefisyè a chwazi resevwa lajan kach.');
        $response->assertDontSee('name="sender_method"', false);
        $response->assertDontSee('name="sender_phone"', false);
        $response->assertDontSee('name="transaction_ref"', false);

        $this->payout($t, $cycle, false, [
            'sender_method' => '',
            'sender_phone' => '',
            'transaction_ref' => '',
            'receipt' => null,
        ])->assertSessionHasNoErrors();

        $payout = Payout::firstOrFail();
        $this->assertSame('cash', $payout->method);
        $this->assertNull($payout->sender_method);
        $this->assertNull($payout->sender_phone);
        $this->assertNull($payout->transaction_ref);
        $this->assertNull($payout->receipt_path);
    }

    public function test_creator_sees_when_the_beneficiary_has_not_said_how_to_be_paid(): void
    {
        [$t, $cycle] = $this->setupPortal();

        $this->actingAs($t->admin)->get(route('tontines.payouts.index', $t))->assertSee('data-no-destination', false);
    }

    public function test_destination_is_snapshotted_in_the_payout(): void
    {
        [$t, $cycle, $ben, $payout] = $this->paidOut();

        $this->assertSame('moncash', $payout->method);
        $this->assertSame('+509 3811 2233', $payout->account);
        $this->assertSame('Marie J.', $payout->account_name);

        // le membre change ensuite ses coordonnées : le versement déjà fait ne bouge pas
        $ben->update(['payout_account' => '+509 9999 9999']);
        $this->assertSame('+509 3811 2233', $payout->fresh()->account);
    }

    public function test_creator_records_transfer_details_and_receipt_for_the_beneficiary(): void
    {
        Storage::fake('local');
        [$t, $cycle] = $this->setupPortal();
        $beneficiary = $cycle->beneficiary;
        $this->enter($beneficiary);
        $this->saveDestination()->assertSessionHasNoErrors();
        $this->payAll($t, $cycle);

        $this->payout($t, $cycle, false, [
            'sender_method' => 'natcash',
            'sender_phone' => '+509 3900 1122',
            'transaction_ref' => 'NC-REF-2026-001',
            'note' => 'Voye nan telefòn manm nan.',
            'receipt' => $this->receiptImage(),
        ])->assertSessionHasNoErrors();

        $payout = Payout::firstOrFail();
        $this->assertSame('natcash', $payout->sender_method);
        $this->assertSame('+509 3900 1122', $payout->sender_phone);
        $this->assertSame('NC-REF-2026-001', $payout->transaction_ref);
        $this->assertSame('+509 3811 2233', $payout->account);
        Storage::disk('local')->assertExists($payout->receipt_path);

        $this->actingAs($t->admin)->get(route('tontines.payouts.index', $t))
            ->assertSee('NC-REF-2026-001')
            ->assertSee(route('tontines.cycles.payout.receipt', [$t, $cycle]));
        $this->get(route('tontines.cycles.payout.receipt', [$t, $cycle]))->assertOk();

        $this->enter($beneficiary);
        $this->get(route('portal.home'))->assertSee('NC-REF-2026-001')
            ->assertSee(route('portal.payout.receipt', $payout));
        $this->get(route('portal.payout.receipt', $payout))->assertOk();
    }

    public function test_transfer_receipt_is_not_available_to_another_organizer_or_member(): void
    {
        Storage::fake('local');
        [$t, $cycle] = $this->setupPortal();
        $this->payAll($t, $cycle);
        $this->payout($t, $cycle, false, ['receipt' => $this->receiptImage()])->assertSessionHasNoErrors();
        $payout = Payout::firstOrFail();

        $stranger = $this->premiumUser();
        $this->actingAs($stranger)->get(route('tontines.cycles.payout.receipt', [$t, $cycle]))->assertForbidden();

        $otherMember = $t->members()->whereKeyNot($payout->member_id)->firstOrFail();
        $this->enter($otherMember);
        $this->get(route('portal.payout.receipt', $payout))->assertNotFound();
    }

    public function test_transfer_receipt_must_be_an_allowed_image(): void
    {
        Storage::fake('local');
        [$t, $cycle] = $this->setupPortal();
        $this->payAll($t, $cycle);

        $this->payout($t, $cycle, false, ['receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('receipt');
        $this->assertSame(0, Payout::count());
    }

    public function test_beneficiary_sees_the_confirmation_card_and_confirms(): void
    {
        [$t, $cycle, $ben, $payout] = $this->paidOut();

        $this->get(route('portal.home'))->assertOk()
            ->assertSee('data-payout-card="awaiting"', false)
            ->assertSee('Mwen resevwa lajan an')
            ->assertDontSee('data-destination-form', false);

        $this->post(route('portal.payout.confirm', $payout))->assertSessionHasNoErrors();

        $this->assertSame('received', $payout->fresh()->member_response);
        $this->assertNotNull($payout->fresh()->member_responded_at);
        $this->get(route('portal.home'))->assertSee('data-payout-card="received"', false);
        $this->actingAs($t->admin)->get(route('tontines.payouts.index', $t))->assertSee('data-payout-received', false);
    }

    public function test_dispute_needs_a_note_alerts_the_creator_and_can_be_reopened(): void
    {
        [$t, $cycle, $ben, $payout] = $this->paidOut();

        $this->post(route('portal.payout.dispute', $payout), [])->assertSessionHasErrors('member_note');
        $this->post(route('portal.payout.dispute', $payout), ['member_note' => 'Mwen pa wè anyen'])->assertSessionHasNoErrors();
        $this->assertSame('not_received', $payout->fresh()->member_response);

        $this->actingAs($t->admin)->get(route('tontines.payouts.index', $t))->assertSee('data-payout-dispute', false)->assertSee('Mwen pa wè anyen');
        $this->actingAs($t->admin)->get(route('tontines.show', $t))->assertSee('data-dispute-alert', false);

        // le créateur vérifie / renvoie, puis demande au membre de confirmer à nouveau
        $this->actingAs($t->admin)->post(route('tontines.cycles.payout.reopen', [$t, $cycle]))->assertSessionHasNoErrors();
        $this->assertNull($payout->fresh()->member_response);
        $this->assertNull($payout->fresh()->member_note);
        $this->actingAs($t->admin)->get(route('tontines.show', $t))->assertDontSee('data-dispute-alert', false);

        $this->post(route('portal.payout.confirm', $payout))->assertSessionHasNoErrors();
        $this->assertSame('received', $payout->fresh()->member_response);
    }

    public function test_member_can_confirm_after_a_dispute_but_not_dispute_after_confirming(): void
    {
        [$t, $cycle, $ben, $payout] = $this->paidOut();

        $this->post(route('portal.payout.dispute', $payout), ['member_note' => 'pa wè'])->assertSessionHasNoErrors();
        $this->post(route('portal.payout.confirm', $payout))->assertSessionHasNoErrors();
        $this->assertSame('received', $payout->fresh()->member_response);

        $this->post(route('portal.payout.dispute', $payout), ['member_note' => 'ankò'])->assertSessionHasErrors('payout');
        $this->assertSame('received', $payout->fresh()->member_response);
    }

    public function test_a_member_cannot_answer_for_someone_elses_payout(): void
    {
        [$t, $cycle, $ben, $payout] = $this->paidOut();
        $other = $t->members()->whereKeyNot($ben->id)->first();
        $this->enter($other);

        $this->post(route('portal.payout.confirm', $payout))->assertNotFound();
        $this->post(route('portal.payout.dispute', $payout), ['member_note' => 'x'])->assertNotFound();
        $this->assertNull($payout->fresh()->member_response);
    }

    public function test_reopen_only_works_on_a_dispute_and_only_for_the_creator(): void
    {
        [$t, $cycle, $ben, $payout] = $this->paidOut();

        $this->actingAs($t->admin)->post(route('tontines.cycles.payout.reopen', [$t, $cycle]))->assertSessionHasErrors('payout');

        $payout->update(['member_response' => 'not_received', 'member_note' => 'x']);
        $stranger = $this->premiumUser();
        $this->actingAs($stranger)->post(route('tontines.cycles.payout.reopen', [$t, $cycle]))->assertForbidden();
        $this->assertSame('not_received', $payout->fresh()->member_response);
    }

    public function test_old_payouts_without_any_response_still_work(): void
    {
        // tontine sans portail : le flux de versement d'avant ne change pas
        $t = $this->start($this->makeTontine(null, 3));
        $cycle = $this->openCycle($t);
        $this->payAll($t, $cycle);
        $this->payout($t, $cycle)->assertSessionHasNoErrors();

        $p = Payout::firstOrFail();
        $this->assertTrue($p->confirmed);
        $this->assertNull($p->member_response);
        $this->actingAs($t->admin)->get(route('tontines.payouts.index', $t))->assertOk()->assertDontSee('data-payout-awaiting', false);
    }

    public function test_member_portal_expires_after_the_final_tontine_payout(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $this->payAll($t, $cycle);
        $this->payout($t, $cycle)->assertSessionHasNoErrors();

        $this->assertSame('active', $t->fresh()->status);
        $this->assertSame('open', $this->openCycle($t)->status);
        $this->actingAs($t->admin)
            ->put(route('tontines.portal.update', $t), ['member_portal_enabled' => 0])
            ->assertSessionHasErrors('member_portal_enabled');
        $this->assertTrue($t->fresh()->member_portal_enabled);

        for ($guard = 0; $guard < 2; $guard++) {
            $cycle = $this->openCycle($t);
            $this->payAll($t, $cycle);
            $this->payout($t, $cycle)->assertSessionHasNoErrors();
        }

        $t->refresh();
        $this->assertSame('active', $t->status);
        $payouts = Payout::orderBy('id')->get();
        $this->assertCount(3, $payouts);
        $lastBeneficiary = $payouts->last()->member;

        $this->enter($lastBeneficiary);
        $this->get(route('portal.home'))->assertOk();
        $this->post(route('portal.payout.confirm', $payouts->last()))->assertSessionHasNoErrors();
        $this->assertSame('active', $t->fresh()->status);

        foreach ($payouts->take(2) as $payout) {
            $this->enter($payout->member);
            $this->post(route('portal.payout.confirm', $payout))->assertSessionHasNoErrors();
        }

        $this->assertSame('completed', $t->fresh()->status);
        $this->get(route('portal.home'))->assertForbidden();
        $this->get($lastBeneficiary->fresh()->portalUrl())->assertNotFound();
    }

    // Régression sécurité : un créateur ne peut pas agir sur le tour (ou la cotisation) d'une AUTRE tontine
    public function test_a_creator_cannot_use_another_tontines_cycle_or_contribution(): void
    {
        $victim = $this->start($this->makeTontine());
        $vc = $this->openCycle($victim);
        $this->pay($victim, $vc, $victim->members()->first())->assertSessionHasNoErrors();
        $contribution = Contribution::firstOrFail();

        $attacker = $this->start($this->makeTontine());

        $this->actingAs($attacker->admin)->post(route('tontines.cycles.payout.store', [$attacker, $vc]), ['confirm_incomplete' => 1])->assertNotFound();
        $this->actingAs($attacker->admin)->delete(route('tontines.cycles.contributions.destroy', [$attacker, $vc, $contribution]))->assertNotFound();
        $this->actingAs($attacker->admin)->post(route('tontines.cycles.payout.reopen', [$attacker, $vc]))->assertNotFound();

        $this->assertSame(0, Payout::count());
        $this->assertSame('open', $vc->fresh()->status);
        $this->assertSame(1, Contribution::count());
    }
}
