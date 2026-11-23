<?php

namespace App\Http\Controllers;

use App\Exceptions\MonCashConnectException;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\MonCashConnect;
use App\Services\OnlinePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Abonnement payé en ligne par MonCash (via MonCashConnect). Le prix vient toujours du plan, jamais du navigateur.
class OnlinePaymentController extends Controller
{
    public function __construct(private MonCashConnect $gateway, private OnlinePaymentService $payments) {}

    public function start(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->isRequestable() && $this->gateway->enabled(), 404);

        $data = $request->validate(['billing_period' => ['required', Rule::in(array_keys(Plan::PERIODS))]]);
        $period = $data['billing_period'];
        $price = $plan->priceFor($period);

        if (! $price || ! $this->gateway->canCharge($price)) {
            return back()->withErrors(['billing_period' => __('Peman anliy pa disponib pou peryòd sa a. Sèvi ak peman manyèl la.')]);
        }

        $user = $request->user();

        // Lien de paiement encore valable pour le même choix : on le reprend (pas de doublon si on clique deux fois)
        $existing = $user->subscriptionPayments()
            ->where('status', 'pending')->where('plan_id', $plan->id)->where('billing_period', $period)
            ->where('amount', $price)->where('expires_at', '>', now()->addMinute())->whereNotNull('payment_url')
            ->latest('id')->first();

        if ($existing) {
            return redirect()->away($existing->payment_url);
        }

        $payment = SubscriptionPayment::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'billing_period' => $period, 'amount' => $price,
            'reference_id' => 'SUB-'.strtoupper(Str::random(16)), 'status' => 'pending',
            'plan_snapshot' => $plan->snapshotData(),
        ]);

        try {
            $res = $this->gateway->createPayment(
                $payment->reference_id, (int) $price,
                rtrim((string) $this->gateway->origin(), '/').route('subscription.online.return', $payment->reference_id, false),
                $user->name, $user->email,
            );
        } catch (MonCashConnectException|\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('MonCashConnect pay-create failed', ['reference' => $payment->reference_id, 'error' => $e->getMessage()]);
            $payment->update(['status' => 'failed', 'failure_reason' => 'create_failed']);

            return back()->withErrors(['billing_period' => __('Nou pa t kapab kreye peman an. Eseye ankò oswa sèvi ak peman manyèl la.')]);
        }

        $payment->update([
            'payment_url' => $res['paymentUrl'],
            'expires_at' => $res['expiresAt'] ? \Illuminate\Support\Carbon::parse($res['expiresAt']) : now()->addMinutes(15),
        ]);

        return redirect()->away($payment->payment_url);
    }

    // Le client revient de MonCash (MCC ajoute ?reference, ?cancelled ou ?error). Le webhook peut arriver plus tard :
    // tant que le paiement est en attente, on affiche une page qui interroge le statut toutes les 3 secondes.
    public function returned(Request $request, string $reference): RedirectResponse|View
    {
        $payment = $this->ownPayment($request, $reference);

        $this->refreshFromGateway($payment);
        $payment->refresh();

        // Annulé ou en erreur côté passerelle, et pas payé entre-temps
        if ($payment->status === 'pending' && ($request->boolean('cancelled') || $request->filled('error'))) {
            $this->payments->settle($payment, 'failed', null, $request->boolean('cancelled') ? 'cancelled' : 'gateway_error');
            $payment->refresh();
        }

        if ($payment->status === 'pending') {
            return view('subscription.payment-pending', ['payment' => $payment]);
        }

        return redirect()->route('subscription')->with(match (true) {
            $payment->status === 'completed' => ['status' => __('Abònman ou aktive! Mèsi.')],
            $payment->status === 'review' => ['error' => __('Peman ou an resevwa men nou gen pou verifye li. Nou kontakte ou talè konsa.')],
            $payment->failure_reason === 'cancelled' => ['error' => __('Peman an anile.')],
            default => ['error' => __('Peman an pa pase. Ou ka eseye ankò.')],
        });
    }

    // Interrogé par la page d'attente (toutes les 3 s, 30 fois au plus)
    public function status(Request $request, string $reference): JsonResponse
    {
        $payment = $this->ownPayment($request, $reference);

        $this->refreshFromGateway($payment);

        return response()->json(['status' => $payment->fresh()->status]);
    }

    private function ownPayment(Request $request, string $reference): SubscriptionPayment
    {
        return SubscriptionPayment::where('reference_id', $reference)->where('user_id', $request->user()->id)->firstOrFail();
    }

    private function refreshFromGateway(SubscriptionPayment $payment): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        try {
            $this->applyGatewayStatus($payment, $this->gateway->status($payment->reference_id));
        } catch (MonCashConnectException|\Illuminate\Http\Client\ConnectionException $e) {
            Log::info('MonCashConnect pay-status unavailable', ['reference' => $payment->reference_id]);
        }
    }

    // Notification de la passerelle. Aucune session : l'authenticité vient UNIQUEMENT de la signature HMAC.
    public function webhook(Request $request): JsonResponse
    {
        $contentLength = $request->server('CONTENT_LENGTH');

        if (is_numeric($contentLength) && (int) $contentLength > 65536) {
            return response()->json(['error' => 'payload_too_large'], 413);
        }

        $raw = $request->getContent();

        if (strlen($raw) > 65536) {
            return response()->json(['error' => 'payload_too_large'], 413);
        }

        if (! $this->gateway->verifySignature($raw, $request->header('X-MCC-Signature-V2'), $request->header('X-MCC-Signature'), $request->header('X-MCC-Timestamp'))) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        try {
            $event = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        $type = is_array($event) ? ($event['event'] ?? null) : null;
        $reference = is_array($event) ? ($event['reference'] ?? null) : null;

        if (! is_string($type) || ! in_array($type, ['payment.completed', 'payment.failed', 'payment.cancelled'], true)) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        if (! is_string($reference)
            || ! preg_match('/\A[A-Za-z0-9_-]{1,100}\z/', $reference)
            || (isset($event['amount']) && ! (is_int($event['amount']) || (is_string($event['amount']) && ctype_digit($event['amount']))))
            || (isset($event['failureReason']) && (! is_string($event['failureReason']) || strlen($event['failureReason']) > 120))) {
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        $payment = SubscriptionPayment::where('reference_id', $reference)->first();

        if (! $payment) {
            Log::warning('MonCashConnect webhook for unknown reference', ['reference' => Str::limit($reference, 60)]);

            return response()->json(['ok' => true, 'ignored' => true]);
        }

        // Échec ou annulation : on le note, rien n'est activé. Toujours 200 (sinon la passerelle réessaie sans fin).
        if ($type !== 'payment.completed') {
            $this->payments->settle($payment, 'failed', null, $type === 'payment.cancelled' ? 'cancelled' : ($event['failureReason'] ?? 'gateway_failed'));

            return response()->json(['ok' => true]);
        }

        $amount = isset($event['amount']) ? (int) $event['amount'] : null;

        // Double contrôle : on ne croit pas le webhook seul, on redemande le statut et le montant à la passerelle
        if (config('services.moncashconnect.verify_with_api')) {
            try {
                $status = $this->gateway->status($reference);
            } catch (MonCashConnectException|\Illuminate\Http\Client\ConnectionException) {
                return response()->json(['error' => 'status_unavailable'], 503);   // la passerelle réessaiera
            }

            if (($status['status'] ?? null) !== 'completed') {
                return response()->json(['error' => 'not_completed_yet'], 409);
            }

            $amount = isset($status['amount']) ? (int) $status['amount'] : $amount;
        }

        $this->payments->settle($payment, 'completed', $amount);

        return response()->json(['ok' => true]);
    }

    private function applyGatewayStatus(SubscriptionPayment $payment, array $status): void
    {
        match ($status['status'] ?? null) {
            'completed' => $this->payments->settle($payment, 'completed', isset($status['amount']) ? (int) $status['amount'] : null),
            'failed' => $this->payments->settle($payment, 'failed', null, $status['failureReason'] ?? 'gateway_failed'),
            'cancelled' => $this->payments->settle($payment, 'failed', null, 'cancelled'),
            default => null,
        };
    }
}
