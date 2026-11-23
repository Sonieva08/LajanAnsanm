<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Member;
use App\Models\PaymentDeclaration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class PaymentDeclarationTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    // Tontine démarrée, portail ouvert, MonCash configuré. Renvoie [tontine, tour ouvert].
    private function setupPortal(int $members = 3): array
    {
        $t = $this->makeTontine(null, $members);
        $this->actingAs($t->admin)->put(route('tontines.portal.update', $t), [
            'member_portal_enabled' => 1, 'moncash_number' => '+509 3700 0000',
            'moncash_qr' => UploadedFile::fake()->createWithContent(
                'moncash.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=', true)
            ),
            'bank_info' => 'BUH 123',
        ])->assertSessionHasNoErrors();
        $t = $this->start($t);

        return [$t, $this->openCycle($t)];
    }

    private function enter(Member $m): void
    {
        $this->get($m->fresh()->portalUrl())->assertRedirect(route('portal.home'));
    }

    private function declare($cycle, array $extra = [])
    {
        return $this->post(route('portal.declare', $cycle), array_merge([
            'payment_method' => 'moncash', 'payer_phone' => '+509 3811 2233', 'transaction_ref' => 'MC-1001',
            'receipt' => UploadedFile::fake()->createWithContent(
                'receipt.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=', true)
            ),
        ], $extra));
    }

    public function test_member_declares_a_payment_and_nothing_is_counted_yet(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $m = $t->members()->where('position', 2)->first();
        $this->enter($m);

        $this->declare($cycle, ['amount' => 1])->assertSessionHasNoErrors()->assertRedirect(route('portal.home'));

        $d = PaymentDeclaration::firstOrFail();
        $this->assertSame('pending', $d->status);
        $this->assertSame(500.0, (float) $d->amount);               // le montant vient de la tontine, pas du navigateur
        $this->assertSame(0, Contribution::count());
        $this->assertSame(0, $cycle->paymentProgress()['paid']);
        $this->get(route('portal.home'))->assertSee('data-declaration-pending', false)->assertDontSee('data-declare-form', false);
    }

    public function test_payment_method_must_be_one_the_creator_configured(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $this->enter($t->members()->first());

        $this->declare($cycle, ['payment_method' => 'natcash'])->assertSessionHasErrors('payment_method');
        $this->declare($cycle, ['transaction_ref' => ''])->assertSessionHasErrors('transaction_ref');
        $this->declare($cycle, ['payer_phone' => 'abc'])->assertSessionHasErrors('payer_phone');
        $this->assertSame(0, PaymentDeclaration::count());
    }

    public function test_bank_declaration_only_requires_a_receipt_and_saves_optional_sender_details_as_null(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $this->enter($t->members()->first());

        $this->declare($cycle, ['payment_method' => 'bank', 'payer_phone' => '', 'transaction_ref' => ''])
            ->assertSessionHasNoErrors();

        $declaration = PaymentDeclaration::firstOrFail();
        $this->assertNull($declaration->payer_phone);
        $this->assertNull($declaration->transaction_ref);
        $this->assertTrue($declaration->hasReceipt());

        $this->enter($t->members()->where('position', 2)->first());
        $this->declare($cycle, ['payment_method' => 'bank', 'payer_phone' => '', 'transaction_ref' => '', 'receipt' => null])
            ->assertSessionHasErrors('receipt');
    }

    public function test_member_can_view_configured_payment_qr_from_the_portal(): void
    {
        [$t] = $this->setupPortal();
        $this->enter($t->members()->first());

        $this->get(route('portal.payment-qr', 'moncash'))->assertOk();
        $this->get(route('tontines.payment-qr', [$t, 'moncash']))->assertOk();
    }

    public function test_cannot_declare_twice_while_pending_nor_reuse_a_reference(): void
    {
        [$t, $cycle] = $this->setupPortal();
        [$a, $b] = [$t->members()->where('position', 1)->first(), $t->members()->where('position', 2)->first()];

        $this->enter($a);
        $this->declare($cycle)->assertSessionHasNoErrors();
        $this->declare($cycle, ['transaction_ref' => 'MC-2'])->assertSessionHasErrors('declaration');

        $this->enter($b);
        $this->declare($cycle, ['transaction_ref' => 'mc-1001'])->assertSessionHasErrors('declaration');   // même référence, casse différente
        $this->assertSame(1, PaymentDeclaration::count());
    }

    public function test_creator_approves_and_a_real_contribution_appears(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $m = $t->members()->where('position', 2)->first();
        $this->enter($m);
        $this->declare($cycle)->assertSessionHasNoErrors();
        $d = PaymentDeclaration::firstOrFail();

        $this->actingAs($t->admin)->get(route('tontines.declarations.index', $t))->assertOk()->assertSee('MC-1001');
        $this->actingAs($t->admin)->post(route('tontines.declarations.approve', [$t, $d]))->assertSessionHasNoErrors();

        $c = Contribution::firstOrFail();
        $this->assertSame($m->id, $c->member_id);
        $this->assertSame(500.0, (float) $c->amount);
        $this->assertSame('moncash', $c->payment_method);
        $this->assertSame('approved', $d->fresh()->status);
        $this->assertSame($c->id, $d->fresh()->contribution_id);
        $this->assertSame(1, $cycle->paymentProgress()['paid']);

        // approuver une 2e fois ne crée pas de doublon
        $this->actingAs($t->admin)->post(route('tontines.declarations.approve', [$t, $d]))->assertSessionHasErrors('declaration');
        $this->assertSame(1, Contribution::count());
    }

    public function test_rejection_needs_a_reason_then_member_can_correct_and_resubmit(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $m = $t->members()->first();
        $this->enter($m);
        $this->declare($cycle)->assertSessionHasNoErrors();
        $d = PaymentDeclaration::firstOrFail();

        $this->actingAs($t->admin)->post(route('tontines.declarations.reject', [$t, $d]), [])->assertSessionHasErrors('review_note');
        $this->actingAs($t->admin)->post(route('tontines.declarations.reject', [$t, $d]), ['review_note' => 'Montan an pa antre'])->assertSessionHasNoErrors();
        $this->assertSame(0, Contribution::count());

        $this->get(route('portal.home'))->assertSee('Montan an pa antre')->assertSee('data-declare-form', false);

        $this->declare($cycle, ['transaction_ref' => 'MC-FIXED'])->assertSessionHasNoErrors();
        $this->assertSame(1, PaymentDeclaration::count());                      // même ligne réutilisée
        $this->assertSame('pending', $d->fresh()->status);
        $this->assertSame('MC-FIXED', $d->fresh()->transaction_ref);
        $this->assertNull($d->fresh()->review_note);
    }

    public function test_cannot_approve_once_the_cycle_is_closed(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $late = $t->members()->where('position', 3)->first();
        $this->enter($late);
        $this->declare($cycle)->assertSessionHasNoErrors();
        $d = PaymentDeclaration::firstOrFail();

        foreach ($t->members()->whereKeyNot($late->id)->get() as $m) {
            $this->pay($t, $cycle, $m)->assertSessionHasNoErrors();
        }
        $this->payout($t, $cycle, true)->assertSessionHasNoErrors();             // tour fermé sans lui

        $this->actingAs($t->admin)->post(route('tontines.declarations.approve', [$t, $d]))->assertSessionHasErrors('declaration');
        $this->assertSame('pending', $d->fresh()->status);
        $this->assertSame(0, Contribution::where('member_id', $late->id)->count());
    }

    public function test_pending_declarations_never_count_in_payouts(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $this->enter($t->members()->first());
        $this->declare($cycle)->assertSessionHasNoErrors();

        $this->payout($t, $cycle, true)->assertSessionHasErrors('payout');      // aucun paiement réel : rien à verser
    }

    public function test_manual_contribution_settles_a_pending_declaration(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $m = $t->members()->first();
        $this->enter($m);
        $this->declare($cycle)->assertSessionHasNoErrors();

        $this->pay($t, $cycle, $m)->assertSessionHasNoErrors();

        $d = PaymentDeclaration::firstOrFail();
        $this->assertSame('approved', $d->status);
        $this->assertSame(Contribution::firstOrFail()->id, $d->contribution_id);
        $this->actingAs($t->admin)->get(route('tontines.show', $t))->assertDontSee('badge-wait">1<', false);
    }

    public function test_only_the_creator_of_that_tontine_can_review(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $this->enter($t->members()->first());
        $this->declare($cycle)->assertSessionHasNoErrors();
        $d = PaymentDeclaration::firstOrFail();

        $stranger = $this->premiumUser();
        $this->actingAs($stranger)->post(route('tontines.declarations.approve', [$t, $d]))->assertForbidden();
        $this->actingAs($stranger)->get(route('tontines.declarations.index', $t))->assertForbidden();

        // un créateur ne peut pas viser la déclaration d'une AUTRE de ses tontines
        $other = $this->makeTontine($t->admin, 2);
        $this->actingAs($t->admin)->post(route('tontines.declarations.approve', [$other, $d]))->assertNotFound();
        $this->assertSame('pending', $d->fresh()->status);
    }

    public function test_member_cannot_declare_for_a_cycle_of_another_tontine_or_a_waiting_cycle(): void
    {
        [$t, $cycle] = $this->setupPortal();
        [$other, $otherCycle] = $this->setupPortal();
        $this->enter($t->members()->first());

        $this->declare($otherCycle)->assertNotFound();

        $waiting = $t->fresh()->roundCycles()->where('status', 'pending')->firstOrFail();
        $this->declare($waiting)->assertSessionHasErrors('declaration');
        $this->assertSame(0, PaymentDeclaration::count());
    }

    public function test_pending_count_shows_on_the_tontine_page(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $this->enter($t->members()->first());
        $this->declare($cycle)->assertSessionHasNoErrors();

        $this->assertSame(1, $t->pendingDeclarationsCount());
        $this->actingAs($t->admin)->get(route('tontines.show', $t))->assertSee('data-declarations-link', false);
    }

    public function test_member_can_upload_receipt_and_creator_can_view_it(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $m = $t->members()->first();
        $this->enter($m);

        $fakePng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('moncash_receipt.png', $fakePng);

        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 2233',
            'transaction_ref' => 'MC-REC-101',
            'receipt' => $file,
        ])->assertSessionHasNoErrors()->assertRedirect(route('portal.home'));

        $d = PaymentDeclaration::where('transaction_ref', 'MC-REC-101')->firstOrFail();
        $this->assertTrue($d->hasReceipt());
        Storage::disk('local')->assertExists($d->receipt_path);

        // Member can view receipt in portal
        $this->get(route('portal.declarations.receipt', $d))->assertOk();

        // Creator can view receipt in admin/tontine area
        $this->actingAs($t->admin)->get(route('tontines.declarations.receipt', [$t, $d]))->assertOk();

        // Stranger cannot view receipt
        $stranger = $this->premiumUser();
        $this->actingAs($stranger)->get(route('tontines.declarations.receipt', [$t, $d]))->assertForbidden();
    }
}
