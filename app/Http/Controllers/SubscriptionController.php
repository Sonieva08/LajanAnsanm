<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\SubscriptionRequest;
use App\Models\Tontine;
use App\Support\Entitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    public function plans(Request $request): View
    {
        $ent = Entitlements::for($request->user());

        return view('plans.index', [
            'plans' => Plan::listed()->where('is_free', false)->get(),
            'ent' => $ent,
            'limit' => session('limit'),
            'recommended' => session('limit') ? $ent->recommendedPlan(session('limit')['need']) : null,
        ]);
    }

    public function show(Request $request): View
    {
        $user = $request->user();

        $membersUsed = (int) Tontine::where('admin_id', $user->id)->where('is_legacy', false)->where('status', '!=', 'completed')
            ->withCount(['members as active_members_count' => fn ($q) => $q->where('is_active', true)])->get()->max('active_members_count');

        return view('subscription.show', [
            'membersUsed' => $membersUsed,
            'payment' => Setting::paymentInfo(),
            'ent' => Entitlements::for($user),
            'latestOnlinePayment' => $user->subscriptionPayments()
                ->with(['plan', 'subscription'])
                ->whereIn('status', ['completed', 'review'])
                ->latest('id')
                ->first(),
            'pending' => $user->subscriptionRequests()->with('plan')->where('status', 'pending')->latest('id')->first(),
            'history' => $user->subscriptionRequests()->with('plan')->latest('id')->limit(10)->get(),
        ]);
    }

    public function requestForm(Request $request, Plan $plan): View|RedirectResponse
    {
        abort_unless($plan->isRequestable(), 404);

        if ($request->user()->subscriptionRequests()->where('status', 'pending')->exists()) {
            return redirect()->route('subscription')->with('status', __('Ou gen yon demann ki ap tann konfimasyon deja.'));
        }

        $period = array_key_exists((string) $request->query('period'), Plan::PERIODS) && $plan->priceFor($request->query('period'))
            ? $request->query('period') : 'monthly';

        return view('plans.request', [
            'plan' => $plan,
            'period' => $period,
            'amount' => $plan->priceFor($period),
            'online' => app(\App\Services\MonCashConnect::class)->canCharge($plan->priceFor($period)),
            'sandbox' => app(\App\Services\MonCashConnect::class)->sandbox(),
            'methods' => Setting::paymentMethods(),
            'payment' => Setting::paymentInfo(),
            'code' => SubscriptionRequest::nextReferenceCode($request->user()),
        ]);
    }

    public function submitRequest(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->isRequestable(), 404);
        $user = $request->user();
        $methods = Setting::paymentMethods();
        $paymentMethod = $request->input('payment_method');
        $transactionIdRequired = in_array($paymentMethod, ['moncash', 'natcash'], true);

        $data = $request->validate([
            'billing_period' => ['required', Rule::in(array_keys(Plan::PERIODS))],
            'payment_method' => ['required', Rule::in(array_keys($methods))],
            'payer_phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\s\-]{6,}$/'],
            'recipient_account' => ['required', 'string', 'max:120'],
            'transaction_ref' => [Rule::requiredIf($transactionIdRequired), 'nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:300'],
            'receipt' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $amount = $plan->priceFor($data['billing_period']);
        if (! $amount || $amount <= 0) {
            return back()->withErrors(['billing_period' => __('Peryòd sa a pa disponib pou plan sa a.')])->withInput();
        }

        if ($user->subscriptionRequests()->where('status', 'pending')->exists()) {
            return redirect()->route('subscription')->with('status', __('Ou gen yon demann ki ap tann konfimasyon deja.'));
        }

        $ref = trim((string) ($data['transaction_ref'] ?? ''));
        $dup = $ref !== '' && SubscriptionRequest::where('payment_method', $data['payment_method'])
            ->whereRaw('lower(transaction_ref) = ?', [mb_strtolower($ref)])
            ->whereIn('status', ['pending', 'approved'])->exists();

        if ($dup) {
            return back()->withErrors(['transaction_ref' => __('Nimewo tranzaksyon sa a deja itilize.')])->withInput();
        }

        $receiptPath = $request->file('receipt')->store('subscription-receipts', 'local');
        if (! $receiptPath) {
            throw new \RuntimeException('Unable to store subscription payment receipt.');
        }

        SubscriptionRequest::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'billing_period' => $data['billing_period'],
            'amount' => $amount, 'payment_method' => $data['payment_method'], 'payer_phone' => $data['payer_phone'] ?? null,
            'recipient_account' => trim($data['recipient_account']),
            'transaction_ref' => $ref !== '' ? $ref : null, 'receipt_path' => $receiptPath,
            'reference_code' => SubscriptionRequest::nextReferenceCode($user), 'note' => $data['note'] ?? null,
            'plan_snapshot' => $plan->snapshotData(),
        ]);

        ActivityLog::record('subscription_requested', $user, ['plan' => $plan->name, 'amount' => $amount]);

        return redirect()->route('subscription')->with('status', __('Nou resevwa deklarasyon peman ou an. Nou ap verifye li.'));
    }

    public function receipt(Request $request, SubscriptionRequest $subscriptionRequest): StreamedResponse
    {
        abort_unless($subscriptionRequest->user_id === $request->user()->id, 404);
        abort_unless($subscriptionRequest->receiptExists(), 404);

        return Storage::disk('local')->response($subscriptionRequest->receipt_path);
    }
}
