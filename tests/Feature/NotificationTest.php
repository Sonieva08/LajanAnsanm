<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Payout;
use App\Notifications\PaymentDeclarationSubmitted;
use App\Notifications\PayoutDisputed;
use App\Notifications\PayoutReceivedConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use BuildsTontines, RefreshDatabase;

    private function receipt(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'receipt.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=', true)
        );
    }

    private function setupPortal(int $members = 3): array
    {
        Storage::fake('local');
        $t = $this->makeTontine(null, $members);
        $this->actingAs($t->admin)->put(route('tontines.portal.update', $t), [
            'member_portal_enabled' => 1,
            'moncash_number' => '+509 3700 0000',
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

    public function test_organizer_receives_notification_when_member_declares_payment(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $member = $t->members()->where('position', 2)->first();
        $this->enter($member);

        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 2233',
            'transaction_ref' => 'MC-NOTIF-01',
            'receipt' => UploadedFile::fake()->createWithContent(
                'receipt.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=', true)
            ),
        ])->assertSessionHasNoErrors();

        $admin = $t->admin->fresh();
        $this->assertSame(1, $admin->unreadNotifications()->count());

        $notification = $admin->unreadNotifications()->first();
        $this->assertSame(PaymentDeclarationSubmitted::class, $notification->type);
        $this->assertSame('payment_declaration', $notification->data['type']);
        $this->assertSame($member->name, $notification->data['member_name']);
        $this->assertSame($t->name, $notification->data['tontine_name']);
    }

    public function test_organizer_receives_notification_when_member_confirms_payout(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $member = $t->members()->where('position', 1)->first();

        $payout = Payout::create([
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount' => 1500,
            'paid_at' => today()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $this->enter($member);

        $this->post(route('portal.payout.confirm', $payout))->assertRedirect(route('portal.home'));

        $admin = $t->admin->fresh();
        $this->assertSame(1, $admin->unreadNotifications()->count());

        $notification = $admin->unreadNotifications()->first();
        $this->assertSame(PayoutReceivedConfirmed::class, $notification->type);
        $this->assertSame('payout_received', $notification->data['type']);
        $this->assertSame($member->name, $notification->data['member_name']);
    }

    public function test_organizer_receives_notification_when_member_disputes_payout(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $member = $t->members()->where('position', 1)->first();

        $payout = Payout::create([
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount' => 1500,
            'paid_at' => today()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $this->enter($member);

        $this->post(route('portal.payout.dispute', $payout), [
            'member_note' => 'Mwen pa jwenn lajan an sou kont mwen.',
        ])->assertRedirect(route('portal.home'));

        $admin = $t->admin->fresh();
        $this->assertSame(1, $admin->unreadNotifications()->count());

        $notification = $admin->unreadNotifications()->first();
        $this->assertSame(PayoutDisputed::class, $notification->type);
        $this->assertSame('payout_disputed', $notification->data['type']);
    }

    public function test_organizer_can_view_notifications_page(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $member = $t->members()->where('position', 2)->first();
        $this->enter($member);

        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 2233',
            'transaction_ref' => 'MC-VIEW-01',
            'receipt' => $this->receipt(),
        ]);

        $response = $this->actingAs($t->admin)->get(route('notifications.index'));
        $response->assertOk();
        $response->assertSee('Notifikasyon');
        $response->assertSee($member->name);
        $response->assertSee($t->name);
    }

    public function test_unread_endpoint_returns_json(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $member = $t->members()->where('position', 2)->first();
        $this->enter($member);

        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 2233',
            'transaction_ref' => 'MC-JSON-01',
            'receipt' => $this->receipt(),
        ]);

        $response = $this->actingAs($t->admin)->getJson(route('notifications.unread'));
        $response->assertOk();
        $response->assertJsonStructure([
            'count',
            'notifications' => [
                '*' => ['id', 'title', 'message', 'action_url', 'icon', 'time_ago'],
            ],
        ]);
        $this->assertSame(1, $response->json('count'));
    }

    public function test_organizer_can_mark_notification_as_read(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $member = $t->members()->where('position', 2)->first();
        $this->enter($member);

        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 2233',
            'transaction_ref' => 'MC-READ-01',
            'receipt' => $this->receipt(),
        ]);

        $admin = $t->admin->fresh();
        $notification = $admin->unreadNotifications()->first();

        $this->actingAs($admin)->post(route('notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_organizer_can_mark_all_notifications_as_read(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $m1 = $t->members()->where('position', 2)->first();
        $m2 = $t->members()->where('position', 3)->first();

        $this->enter($m1);
        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 2233',
            'transaction_ref' => 'MC-M1',
            'receipt' => $this->receipt(),
        ]);

        $this->enter($m2);
        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 4455',
            'transaction_ref' => 'MC-M2',
            'receipt' => $this->receipt(),
        ]);

        $admin = $t->admin->fresh();
        $this->assertSame(2, $admin->unreadNotifications()->count());

        $this->actingAs($admin)->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_organizer_can_delete_notification(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $m1 = $t->members()->where('position', 2)->first();
        $this->enter($m1);

        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 2233',
            'transaction_ref' => 'MC-DEL-01',
            'receipt' => $this->receipt(),
        ]);

        $admin = $t->admin->fresh();
        $notification = $admin->notifications()->first();

        $this->actingAs($admin)->delete(route('notifications.destroy', $notification->id))
            ->assertRedirect();

        $this->assertSame(0, $admin->fresh()->notifications()->count());
    }

    public function test_organizer_can_clear_read_notifications(): void
    {
        [$t, $cycle] = $this->setupPortal();
        $m1 = $t->members()->where('position', 2)->first();
        $this->enter($m1);

        $this->post(route('portal.declare', $cycle), [
            'payment_method' => 'moncash',
            'payer_phone' => '+509 3811 2233',
            'transaction_ref' => 'MC-CLR-01',
            'receipt' => $this->receipt(),
        ]);

        $admin = $t->admin->fresh();
        $admin->unreadNotifications->markAsRead();

        $this->actingAs($admin)->delete(route('notifications.clear-read'))
            ->assertRedirect();

        $this->assertSame(0, $admin->fresh()->notifications()->count());
    }
}
