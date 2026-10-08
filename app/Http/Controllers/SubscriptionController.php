<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\SubscriptionRequest;
use App\Support\Entitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function plans(Request $request): View
    {
        $ent = Entitlements::for($request->user());

        return view('plans.index', [
            'plans' => Plan::listed()->get(),
            'ent' => $ent,
            'limit' => session('limit'),
            'recommended' => session('limit') ? $ent->recommendedPlan(session('limit')['need']) : null,
        ]);
    }

    public function show(Request $request): View
    {
        $user = $request->user();

        return view('subscription.show', [
            'ent' => Entitlements::for($user),
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

        $data = $request->validate([
            'billing_period' => ['required', Rule::in(array_keys(Plan::PERIODS))],
            'payment_method' => ['required', Rule::in(array_keys($methods))],
            'payer_phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+\s\-]{6,}$/'],
            'transaction_ref' => ['required', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $amount = $plan->priceFor($data['billing_period']);
        if (! $amount || $amount <= 0) {
            return back()->withErrors(['billing_period' => __('Peryòd sa a pa disponib pou plan sa a.')])->withInput();
        }

        if ($user->subscriptionRequests()->where('status', 'pending')->exists()) {
            return redirect()->route('subscription')->with('status', __('Ou gen yon demann ki ap tann konfimasyon deja.'));
        }

        $ref = trim($data['transaction_ref']);
        $dup = SubscriptionRequest::where('payment_method', $data['payment_method'])
            ->whereRaw('lower(transaction_ref) = ?', [mb_strtolower($ref)])
            ->whereIn('status', ['pending', 'approved'])->exists();

        if ($dup) {
            return back()->withErrors(['transaction_ref' => __('Nimewo tranzaksyon sa a deja itilize.')])->withInput();
        }

        SubscriptionRequest::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'billing_period' => $data['billing_period'],
            'amount' => $amount, 'payment_method' => $data['payment_method'], 'payer_phone' => $data['payer_phone'],
            'transaction_ref' => $ref, 'reference_code' => SubscriptionRequest::nextReferenceCode($user), 'note' => $data['note'] ?? null,
        ]);

        ActivityLog::record('subscription_requested', $user, ['plan' => $plan->name, 'amount' => $amount]);

        return redirect()->route('subscription')->with('status', __('Nou resevwa deklarasyon peman ou an. Nou ap verifye li.'));
    }
}
