<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SubscriptionRequest;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true) ? $request->query('status') : 'pending';

        return view('admin.requests.index', [
            'status' => $status,
            'counts' => SubscriptionRequest::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'requests' => SubscriptionRequest::with(['user', 'plan', 'reviewer'])
                ->where('status', $status)->orderByRaw("case when status = 'pending' then created_at end asc")->latest('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function approve(Request $request, SubscriptionRequest $subscriptionRequest, SubscriptionService $service): RedirectResponse
    {
        $data = $request->validate([
            'ends_at' => ['nullable', 'date_format:Y-m-d', 'after:today'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $ends = isset($data['ends_at']) ? Carbon::createFromFormat('Y-m-d', $data['ends_at'])->endOfDay() : null;

        $sub = $service->approve($subscriptionRequest, $request->user(), $ends, $data['note'] ?? null);

        if (! $sub) {
            return back()->withErrors(['request' => __('Demann sa a deja trete.')]);
        }

        return back()->with('status', __('Abònman aktive jiska') . ' ' . $sub->ends_at->format('d/m/Y') . '.');
    }

    public function reject(Request $request, SubscriptionRequest $subscriptionRequest, SubscriptionService $service): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:300']]);

        if (! $service->reject($subscriptionRequest, $request->user(), $data['note'])) {
            return back()->withErrors(['request' => __('Demann sa a deja trete.')]);
        }

        return back()->with('status', __('Demann rejte.'));
    }
}
