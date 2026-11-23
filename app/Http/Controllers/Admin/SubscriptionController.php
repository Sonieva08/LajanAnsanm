<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = in_array($status, ['active', 'expired', 'replaced', 'cancelled'], true) ? $status : 'all';

        $source = $request->query('source');
        $source = in_array($source, ['online', 'request', 'owner_grant'], true) ? $source : 'all';

        $search = trim((string) $request->query('q', ''));

        $subscriptions = Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name', 'payment'])
            ->when($status === 'active', fn ($query) => $query->where('status', 'active')->where('ends_at', '>=', now()))
            ->when($status === 'expired', fn ($query) => $query->where('status', 'active')->where('ends_at', '<', now()))
            ->when(in_array($status, ['replaced', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($source !== 'all', fn ($query) => $query->where('source', $source))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('plan', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('payment', fn ($query) => $query->where('reference_id', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(15, ['*'], 'subscriptions_page')
            ->withQueryString();

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'payments' => SubscriptionPayment::with(['user:id,name,email', 'plan:id,name'])
                ->latest('id')
                ->paginate(10, ['*'], 'payments_page')
                ->withQueryString(),
            'status' => $status,
            'source' => $source,
            'search' => $search,
            'counts' => [
                'all' => Subscription::count(),
                'active' => Subscription::where('status', 'active')->where('ends_at', '>=', now())->count(),
                'expired' => Subscription::where('status', 'active')->where('ends_at', '<', now())->count(),
                'replaced' => Subscription::where('status', 'replaced')->count(),
                'cancelled' => Subscription::where('status', 'cancelled')->count(),
            ],
        ]);
    }
}
