<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const WHSEC = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.moncashconnect' => [
            'enabled' => true, 'secret' => 'sk_test_proj_abc', 'webhook_secret' => self::WHSEC,
            'base_url' => 'https://api.moncashconnect.com/v1', 'verify_with_api' => true,
            'origin' => 'https://lajanansanm.test', 'max_amount' => 1000000,
        ]]);

        Plan::where('slug', 'pro')->update(['is_active' => true, 'price_monthly' => 2500, 'price_quarterly' => 6750, 'price_yearly' => 1200000]);
    }

    private function pro(): Plan
    {
        return Plan::where('slug', 'pro')->firstOrFail();
    }

    private function fakeGateway(string $status = 'completed', int $amount = 2500, int $statusHttp = 200): void
    {
        Http::fake([
            'api.moncashconnect.com/v1/pay-create' => Http::response([
                'paymentUrl' => 'https://pay.moncashconnect.com/c/abc123', 'reference' => 'pay_1', 'expiresAt' => now()->addMinutes(15)->toIso8601String(),
            ]),
            'api.moncashconnect.com/v1/pay-status*' => Http::response($statusHttp === 200 ? ['status' => $status, 'amount' => $amount] : '', $statusHttp),
        ]);
    }

    private function startPayment(User $u, string $period = 'monthly')
    {
        return $this->actingAs($u)->post(route('plans.pay-online', $this->pro()), ['billing_period' => $period]);
    }

    /** Envoie un webhook signé comme le ferait MonCashConnect. */
    private function webhook(array $payload, array $opts = [])
    {
        $raw = json_encode($payload);
        $ts = (string) ($opts['ts'] ?? time());
        $secret = $opts['secret'] ?? self::WHSEC;
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X-MCC-TIMESTAMP' => $ts];

        if ($opts['v2'] ?? true) {
            $headers['HTTP_X-MCC-SIGNATURE-V2'] = 'sha256='.hash_hmac('sha256', $ts.'.'.$raw, $secret);
        }
        if ($opts['v1'] ?? false) {
            $headers['HTTP_X-MCC-SIGNATURE'] = 'sha256='.hash_hmac('sha256', $raw, $opts['v1secret'] ?? $secret);
        }

        return $this->call('POST', route('webhooks.moncashconnect'), [], [], [], $headers, $raw);
    }

    private function completedEvent(SubscriptionPayment $p, int $amount = 2500): array
    {
        return ['event' => 'payment.completed', 'reference' => $p->reference_id, 'amount' => $amount, 'status' => 'completed'];
    }

    private function pending(User $u): SubscriptionPayment
    {
        $this->startPayment($u)->assertRedirect();

        return SubscriptionPayment::firstOrFail();
    }

    // ───────────── démarrage du paiement ─────────────

    public function test_start_creates_a_payment_with_the_server_price_and_redirects_to_the_gateway(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();

        $this->actingAs($u)->post(route('plans.pay-online', $this->pro()), ['billing_period' => 'monthly', 'amount' => 1])
            ->assertRedirect('https://pay.moncashconnect.com/c/abc123');

        $p = SubscriptionPayment::firstOrFail();
        $this->assertSame(2500.0, (float) $p->amount);                     // le prix vient du plan, pas du formulaire
        $this->assertSame('pending', $p->status);
        $this->assertStringStartsWith('SUB-', $p->reference_id);
        $this->assertSame('Pro', $p->plan_snapshot['name']);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.moncashconnect.com/v1/pay-create'
            && $r->hasHeader('Authorization', 'Bearer sk_test_proj_abc')
            && $r->hasHeader('Idempotency-Key', $p->reference_id)
            && $r['amount'] === 2500
            && $r['referenceId'] === $p->reference_id
            && $r['returnUrl'] === 'https://lajanansanm.test'.route('subscription.online.return', $p->reference_id, false));
    }

    public function test_clicking_twice_reuses_the_same_payment_link(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();

        $this->startPayment($u)->assertRedirect('https://pay.moncashconnect.com/c/abc123');
        $this->startPayment($u)->assertRedirect('https://pay.moncashconnect.com/c/abc123');

        $this->assertSame(1, SubscriptionPayment::count());
        Http::assertSentCount(1);
    }

    public function test_online_payment_is_refused_when_disabled_or_price_not_chargeable(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();

        $this->startPayment($u, 'yearly')->assertSessionHasErrors('billing_period');    // 1 200 000 HTG > plafond de 1 000 000
        $this->assertSame(0, SubscriptionPayment::count());

        $this->pro()->update(['price_quarterly' => 6750.50]);                           // pas un montant entier
        $this->startPayment($u, 'quarterly')->assertSessionHasErrors('billing_period');

        config(['services.moncashconnect.enabled' => false]);
        $this->startPayment($u)->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_a_gateway_error_marks_the_payment_failed_and_does_not_redirect(): void
    {
        Http::fake(['api.moncashconnect.com/v1/pay-create' => Http::response(['error' => 'boom'], 500)]);
        $u = User::factory()->create();

        $this->startPayment($u)->assertSessionHasErrors('billing_period');
        $this->assertSame('failed', SubscriptionPayment::firstOrFail()->status);
    }

    public function test_request_page_shows_the_online_button_only_when_available(): void
    {
        $u = User::factory()->create();
        Storage::fake('local');
        Storage::disk('local')->put('subscription-payment-qrs/moncash.png', 'QR image');
        Setting::set('payment', [
            'moncash_number' => '+509 3700 0000',
            'moncash_qr_path' => 'subscription-payment-qrs/moncash.png',
        ]);

        try {
            $this->actingAs($u)->get(route('plans.request', $this->pro()))
                ->assertOk()
                ->assertSee('data-online-payment', false)
                ->assertSee('data-payment-option="online"', false)
                ->assertSee('data-payment-option="manual"', false)
                ->assertSee('data-manual-payment', false)
                ->assertSee('images/moncash_1.png')
                ->assertSee('images/natcash_1.jpg');

            config(['services.moncashconnect.enabled' => false]);
            $this->actingAs($u)->get(route('plans.request', $this->pro()))
                ->assertDontSee('data-online-payment', false)
                ->assertDontSee('data-payment-option="online"', false)
                ->assertSee('data-payment-option="manual"', false);

            // jamais le bouton pour un montant que la passerelle refuserait
            config(['services.moncashconnect.enabled' => true]);
            $this->actingAs($u)->get(route('plans.request', [$this->pro(), 'period' => 'yearly']))->assertOk()->assertDontSee('data-online-payment', false);
        } finally {
            Setting::set('payment', []);
        }
    }

    public function test_unverified_authenticated_users_can_access_online_subscription_payment(): void
    {
        $this->fakeGateway();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('plans.request', $this->pro()))
            ->assertOk()
            ->assertSee('data-online-payment', false);

        $this->actingAs($user)->post(route('plans.pay-online', $this->pro()), ['billing_period' => 'monthly'])
            ->assertRedirect('https://pay.moncashconnect.com/c/abc123');
    }

    public function test_moncashconnect_service_settings_are_loaded_from_environment_configuration(): void
    {
        $services = require config_path('services.php');
        $settings = $services['moncashconnect'] ?? null;

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('enabled', $settings);
        $this->assertArrayHasKey('secret', $settings);
        $this->assertArrayHasKey('webhook_secret', $settings);
        $this->assertArrayHasKey('origin', $settings);
        $this->assertNotEmpty($settings['base_url']);
        $this->assertArrayHasKey('verify_with_api', $settings);
        $this->assertArrayHasKey('max_amount', $settings);
    }

    // ───────────── webhook ─────────────

    public function test_valid_webhook_activates_the_subscription(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->webhook($this->completedEvent($p))->assertOk();

        $this->assertSame('completed', $p->fresh()->status);
        $sub = Subscription::where('user_id', $u->id)->firstOrFail();
        $this->assertSame($this->pro()->id, $sub->plan_id);
        $this->assertSame('active', $sub->status);
        $this->assertSame('online', $sub->source);
        $this->assertSame(2500.0, (float) $sub->amount);
        $this->assertSame('Pro', $sub->plan_snapshot['name']);
        $this->assertTrue($sub->ends_at->between(now()->addMonthsNoOverflow(1)->subMinute(), now()->addMonthsNoOverflow(1)->addMinute()));
        $this->assertSame($sub->id, $p->fresh()->subscription_id);
        $this->assertTrue(ActivityLog::where('action', 'subscription_activated')->exists());
    }

    public function test_webhook_replay_never_activates_twice(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->webhook($this->completedEvent($p))->assertOk();
        $this->webhook($this->completedEvent($p))->assertOk();
        $this->webhook($this->completedEvent($p))->assertOk();

        $this->assertSame(1, Subscription::where('user_id', $u->id)->count());
    }

    public function test_invalid_signatures_are_rejected_and_change_nothing(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);
        $event = $this->completedEvent($p);

        $this->webhook($event, ['secret' => 'whsec_attacker'])->assertUnauthorized();                  // mauvaise clé
        $this->webhook($event, ['ts' => time() - 3600])->assertUnauthorized();                          // trop ancien (rejeu)
        $this->webhook($event, ['v2' => false])->assertUnauthorized();                                  // aucune signature
        $this->call('POST', route('webhooks.moncashconnect'), [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($event))->assertUnauthorized();

        // V2 invalide : on ne retombe PAS sur une V1 valide
        $this->webhook($event, ['secret' => 'whsec_attacker', 'v1' => true])->assertUnauthorized();

        $this->assertSame('pending', $p->fresh()->status);
        $this->assertSame(0, Subscription::where('user_id', $u->id)->count());
    }

    public function test_webhook_rejects_oversized_payloads_before_signature_validation(): void
    {
        $response = $this->call('POST', route('webhooks.moncashconnect'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], str_repeat('x', 65537));

        $response->assertStatus(413)->assertExactJson(['error' => 'payload_too_large']);
    }

    public function test_webhook_rejects_invalid_event_schemas(): void
    {
        $response = $this->webhook([
            'event' => 'payment.completed',
            'reference' => ['not-a-reference'],
            'amount' => 2500,
        ]);

        $response->assertStatus(400)->assertExactJson(['error' => 'invalid_payload']);
    }

    public function test_a_body_tampered_after_signing_is_rejected(): void
    {
        $this->fakeGateway();
        $p = $this->pending(User::factory()->create());
        $raw = json_encode($this->completedEvent($p, 2500));
        $ts = (string) time();
        $sig = 'sha256='.hash_hmac('sha256', $ts.'.'.$raw, self::WHSEC);

        $tampered = str_replace('2500', '1', $raw);
        $this->call('POST', route('webhooks.moncashconnect'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X-MCC-TIMESTAMP' => $ts, 'HTTP_X-MCC-SIGNATURE-V2' => $sig], $tampered)
            ->assertUnauthorized();
    }

    public function test_v1_signature_alone_is_accepted_with_a_fresh_timestamp(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->webhook($this->completedEvent($p), ['v2' => false, 'v1' => true])->assertOk();
        $this->assertSame('completed', $p->fresh()->status);
    }

    public function test_webhook_is_not_trusted_alone_the_gateway_status_must_agree(): void
    {
        $this->fakeGateway('pending');
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->webhook($this->completedEvent($p))->assertStatus(409);                    // la passerelle réessaiera plus tard
        $this->assertSame('pending', $p->fresh()->status);
        $this->assertSame(0, Subscription::where('user_id', $u->id)->count());
    }

    public function test_gateway_unreachable_during_webhook_asks_for_a_retry(): void
    {
        $this->fakeGateway('completed', 2500, 503);
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->webhook($this->completedEvent($p))->assertStatus(503);
        $this->assertSame('pending', $p->fresh()->status);
    }

    public function test_amount_mismatch_goes_to_review_and_does_not_activate(): void
    {
        $this->fakeGateway('completed', 100);                                              // la passerelle dit avoir encaissé 100 HTG
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->webhook($this->completedEvent($p, 100))->assertOk();

        $this->assertSame('review', $p->fresh()->status);
        $this->assertSame(0, Subscription::where('user_id', $u->id)->count());
        $this->assertTrue(ActivityLog::where('action', 'subscription_payment_review')->exists());
    }

    public function test_failed_event_marks_failed_without_activation(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->webhook(['event' => 'payment.failed', 'reference' => $p->reference_id, 'failureReason' => 'insufficient_funds'])->assertOk();

        $this->assertSame('failed', $p->fresh()->status);
        $this->assertSame('insufficient_funds', $p->fresh()->failure_reason);
        $this->assertSame(0, Subscription::where('user_id', $u->id)->count());
    }

    public function test_money_taken_after_a_failure_still_activates(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);
        $p->update(['status' => 'failed']);

        $this->webhook($this->completedEvent($p))->assertOk();

        $this->assertSame('completed', $p->fresh()->status);
        $this->assertSame(1, Subscription::where('user_id', $u->id)->count());
        $this->actingAs($u)->get(route('subscription'))
            ->assertOk()
            ->assertSee('data-online-payment-receipt', false)
            ->assertSee($p->reference_id)
            ->assertSee('MonCashConnect');
    }

    public function test_unknown_reference_and_unknown_events_are_ignored_with_200(): void
    {
        $this->fakeGateway();

        $this->webhook(['event' => 'payment.completed', 'reference' => 'SUB-DOESNOTEXIST', 'amount' => 2500])->assertOk()->assertJson(['ignored' => true]);
        $this->webhook(['event' => 'payout.completed', 'reference' => 'x'])->assertOk()->assertJson(['ignored' => true]);
        $this->webhook(['garbage' => true])->assertOk();
        $this->assertSame(0, Subscription::count());
    }

    public function test_renewing_the_same_plan_extends_from_the_current_end(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $current = Subscription::create([
            'user_id' => $u->id, 'plan_id' => $this->pro()->id, 'status' => 'active', 'billing_period' => 'monthly',
            'starts_at' => now()->subDays(20), 'ends_at' => now()->addDays(10), 'amount' => 2500, 'source' => 'request',
        ]);
        $p = $this->pending($u);

        $this->webhook($this->completedEvent($p))->assertOk();

        $new = Subscription::where('user_id', $u->id)->where('status', 'active')->firstOrFail();
        $this->assertSame('replaced', $current->fresh()->status);
        $this->assertTrue($new->starts_at->isSameDay($current->ends_at));
        $this->assertTrue($new->ends_at->isSameDay($current->ends_at->copy()->addMonth()));
    }

    // ───────────── retour du client + sécurité des routes ─────────────

    public function test_return_page_activates_when_the_gateway_confirms_even_without_the_webhook(): void
    {
        $this->fakeGateway('completed');
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->actingAs($u)->get(route('subscription.online.return', $p->reference_id))
            ->assertRedirect(route('subscription'))->assertSessionHas('status');

        $this->assertSame('completed', $p->fresh()->status);
        $this->assertSame(1, Subscription::where('user_id', $u->id)->count());
    }

    public function test_return_page_while_still_pending_does_not_activate(): void
    {
        $this->fakeGateway('pending');
        $u = User::factory()->create();
        $p = $this->pending($u);
        app()->detectEnvironment(fn () => 'production');
        config(['app.force_https' => false]);

        // en attente : page qui interroge le statut toutes les 3 s, rien n'est activé
        $response = $this->actingAs($u)->get(route('subscription.online.return', $p->reference_id))
            ->assertOk()
            ->assertSee('data-payment-pending', false)
            ->assertSee($this->pro()->name)
            ->assertSee('2,500 HTG')
            ->assertSee($p->reference_id)
            ->assertSee('pending-timeout');

        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertIsString($policy);
        $this->assertSame(1, preg_match('/nonce-([A-Za-z0-9]{32})/', $policy, $matches));
        $response->assertSee('nonce="'.$matches[1].'"', false);

        $this->assertSame('pending', $p->fresh()->status);
        $this->assertSame(0, Subscription::where('user_id', $u->id)->count());
    }

    public function test_return_page_survives_the_gateway_being_down(): void
    {
        $this->fakeGateway('completed', 2500, 500);
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->actingAs($u)->get(route('subscription.online.return', $p->reference_id))->assertOk()->assertSee('data-payment-pending', false);
        $this->assertSame('pending', $p->fresh()->status);
    }

    public function test_nobody_can_use_someone_elses_payment_reference(): void
    {
        $this->fakeGateway();
        $owner = User::factory()->create();
        $p = $this->pending($owner);

        $this->actingAs(User::factory()->create())->get(route('subscription.online.return', $p->reference_id))->assertNotFound();
        auth()->logout();
        $this->get(route('subscription.online.return', $p->reference_id))->assertRedirect(route('login'));
    }

    public function test_webhook_route_is_csrf_exempt_and_works_during_maintenance(): void
    {
        $route = app('router')->getRoutes()->getByName('webhooks.moncashconnect');
        $this->assertContains(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $route->excludedMiddleware());

        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);
        \App\Models\Setting::set('maintenance', '1');

        $this->webhook($this->completedEvent($p))->assertOk();
        $this->assertSame('completed', $p->fresh()->status);
    }

    public function test_misconfigured_webhook_secret_rejects_everything(): void
    {
        $this->fakeGateway();
        $p = $this->pending(User::factory()->create());
        config(['services.moncashconnect.webhook_secret' => '']);

        $this->webhook($this->completedEvent($p), ['secret' => ''])->assertUnauthorized();
        $this->assertSame('pending', $p->fresh()->status);
    }

    // ───────────── contrat du dashboard MonCashConnect ─────────────

    public function test_origin_header_is_sent_to_the_gateway(): void
    {
        $this->fakeGateway();
        $this->pending(User::factory()->create());

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/pay-create') && $r->hasHeader('Origin', 'https://lajanansanm.test'));
    }

    public function test_origin_falls_back_to_app_url_and_is_required(): void
    {
        config(['services.moncashconnect.origin' => null, 'app.url' => 'https://monsite.ht/sous-dossier']);
        $this->assertSame('https://monsite.ht', app(\App\Services\MonCashConnect::class)->origin());
        $this->assertTrue(app(\App\Services\MonCashConnect::class)->enabled());

        config(['app.url' => 'pas-une-url']);
        $this->assertFalse(app(\App\Services\MonCashConnect::class)->enabled());          // sans domaine, la passerelle refuserait
    }

    public function test_pay_status_is_queried_with_the_reference(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->actingAs($u)->getJson(route('subscription.online.status', $p->reference_id))->assertOk();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/pay-status') && str_contains($r->url(), 'reference='.$p->reference_id));
    }

    public function test_cancelled_webhook_marks_the_payment_without_activation(): void
    {
        $this->fakeGateway();
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->webhook(['event' => 'payment.cancelled', 'reference' => $p->reference_id, 'amount' => 2500, 'status' => 'cancelled', 'completedAt' => null])->assertOk();

        $this->assertSame('failed', $p->fresh()->status);
        $this->assertSame('cancelled', $p->fresh()->failure_reason);
        $this->assertSame(0, Subscription::where('user_id', $u->id)->count());
    }

    public function test_sandbox_payload_without_or_with_livemode_is_handled_the_same(): void
    {
        $this->fakeGateway();
        $a = $this->pending(User::factory()->create());

        // livemode:false (sandbox) ne doit rien changer ; son absence (live) non plus
        $this->webhook($this->completedEvent($a) + ['livemode' => false, 'completedAt' => now()->toIso8601String()])->assertOk();
        $this->assertSame('completed', $a->fresh()->status);
    }

    public function test_return_with_cancelled_flag_ends_as_cancelled_when_not_paid(): void
    {
        $this->fakeGateway('pending');
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->actingAs($u)->get(route('subscription.online.return', [$p->reference_id, 'cancelled' => 1]))
            ->assertRedirect(route('subscription'))->assertSessionHas('error');

        $this->assertSame('failed', $p->fresh()->status);
        $this->assertSame('cancelled', $p->fresh()->failure_reason);
    }

    public function test_cancelled_flag_cannot_cancel_a_payment_the_gateway_says_is_completed(): void
    {
        $this->fakeGateway('completed');
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->actingAs($u)->get(route('subscription.online.return', [$p->reference_id, 'cancelled' => 1]))->assertRedirect(route('subscription'));

        $this->assertSame('completed', $p->fresh()->status);
        $this->assertSame(1, Subscription::where('user_id', $u->id)->count());
    }

    public function test_status_endpoint_reports_and_only_for_the_owner(): void
    {
        // 1er appel : en attente ; 2e appel : payé
        Http::fake([
            'api.moncashconnect.com/v1/pay-create' => Http::response(['paymentUrl' => 'https://pay.moncashconnect.com/c/abc123', 'expiresAt' => now()->addMinutes(15)->toIso8601String()]),
            'api.moncashconnect.com/v1/pay-status*' => Http::sequence()->push(['status' => 'pending', 'amount' => 2500])->push(['status' => 'completed', 'amount' => 2500]),
        ]);
        $u = User::factory()->create();
        $p = $this->pending($u);

        $this->actingAs($u)->getJson(route('subscription.online.status', $p->reference_id))->assertOk()->assertJson(['status' => 'pending']);
        $this->actingAs($u)->getJson(route('subscription.online.status', $p->reference_id))->assertOk()->assertJson(['status' => 'completed']);

        $this->actingAs(User::factory()->create())->getJson(route('subscription.online.status', $p->reference_id))->assertNotFound();
    }

    public function test_sandbox_badge_shows_only_with_test_keys(): void
    {
        $u = User::factory()->create();

        config(['services.moncashconnect.secret' => 'sk_test_proj_abc']);
        $this->actingAs($u)->get(route('plans.request', $this->pro()))->assertSee('data-sandbox', false);

        config(['services.moncashconnect.secret' => 'sk_proj_live']);
        $this->actingAs($u)->get(route('plans.request', $this->pro()))->assertDontSee('data-sandbox', false);
    }

    public function test_max_amount_is_configurable(): void
    {
        $gw = app(\App\Services\MonCashConnect::class);
        $this->assertTrue($gw->canCharge(1000000.0));
        $this->assertFalse($gw->canCharge(1000001.0));

        config(['services.moncashconnect.max_amount' => 100000]);
        $this->assertFalse($gw->canCharge(120000.0));
        $this->assertTrue($gw->canCharge(100000.0));
    }
}
