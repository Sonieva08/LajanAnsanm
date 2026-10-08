<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contribution;
use App\Models\Member;
use App\Models\Plan;
use App\Services\SubscriptionService;
use App\Support\Entitlements;
use Illuminate\Support\Carbon;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filter = in_array($request->query('status'), ['active', 'inactive', 'disabled'], true) ? $request->query('status') : 'all';
        $q = trim((string) $request->query('q', ''));

        $query = User::select('users.*')
            ->addSelect([
                'members_total' => Member::selectRaw('count(*)')
                    ->join('tontines', 'tontines.id', '=', 'members.tontine_id')
                    ->whereColumn('tontines.admin_id', 'users.id')->where('members.is_active', true),
                'volume_total' => Contribution::selectRaw('coalesce(sum(contributions.amount), 0)')
                    ->join('cycles', 'cycles.id', '=', 'contributions.cycle_id')
                    ->join('tontines', 'tontines.id', '=', 'cycles.tontine_id')
                    ->whereColumn('tontines.admin_id', 'users.id'),
            ])
            ->withCount('tontines')
            ->where('is_owner', false)
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when($filter === 'active', fn ($b) => $b->whereNull('disabled_at')->where('last_seen_at', '>=', now()->subDays(30)))
            ->when($filter === 'inactive', fn ($b) => $b->whereNull('disabled_at')
                ->where(fn ($w) => $w->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subDays(30))))
            ->when($filter === 'disabled', fn ($b) => $b->whereNotNull('disabled_at'))
            ->latest('users.id');

        $base = User::where('is_owner', false);
        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereNull('disabled_at')->where('last_seen_at', '>=', now()->subDays(30))->count(),
            'disabled' => (clone $base)->whereNotNull('disabled_at')->count(),
            'new7' => (clone $base)->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('admin.users', [
            'users' => $query->paginate(10)->withQueryString(),
            'filter' => $filter,
            'q' => $q,
            'stats' => $stats,
            'selected' => $this->detail($request->query('user')),
        ]);
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        // On ne peut désactiver ni un propriétaire ni soi-même
        abort_if($user->is_owner || $user->id === $request->user()->id, 403);

        $user->forceFill(['disabled_at' => $user->disabled_at ? null : now()])->save();

        ActivityLog::record($user->disabled_at ? 'account_disabled' : 'account_enabled', $request->user(), ['target_user_id' => $user->id]);

        return back()->with('status', $user->disabled_at ? __('Kont dezaktive.') : __('Kont reaktive.'));
    }

    public function grant(Request $request, User $user, SubscriptionService $service): RedirectResponse
    {
        abort_if($user->is_owner, 403);

        $data = $request->validate([
            'plan_id' => ['required', \Illuminate\Validation\Rule::exists('plans', 'id')->where('is_free', 0)],
            'ends_at' => ['required', 'date_format:Y-m-d', 'after:today'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $sub = $service->grant($user, Plan::findOrFail($data['plan_id']), Carbon::createFromFormat('Y-m-d', $data['ends_at'])->endOfDay(), $request->user(), $data['note'] ?? null);

        return back()->with('status', __('Abònman bay jiska') . ' ' . $sub->ends_at->format('d/m/Y') . '.');
    }

    public function revoke(Request $request, User $user, SubscriptionService $service): RedirectResponse
    {
        abort_if($user->is_owner, 403);

        $service->cancel($user, $request->user());

        return back()->with('status', __('Itilizatè a tounen sou plan gratis la.'));
    }

    // Panneau de détail : chiffres de compte uniquement, aucun nom ni téléphone de membre
    private function detail(mixed $id): ?array
    {
        $user = $id ? User::where('is_owner', false)->find((int) $id) : null;

        if (! $user) {
            return null;
        }

        return [
            'user' => $user,
            'tontines_total' => $user->tontines()->count(),
            'tontines_active' => $user->tontines()->where('status', 'active')->count(),
            'tontines_completed' => $user->tontines()->where('status', 'completed')->count(),
            'members' => Member::whereIn('tontine_id', $user->tontines()->select('id'))->where('is_active', true)->count(),
            'volume' => (float) Contribution::whereHas('cycle.tontine', fn ($q) => $q->where('admin_id', $user->id))->sum('amount'),
            'logs' => $user->activityLogs()->latest('id')->limit(6)->get(),
            'ent' => Entitlements::for($user),
            'plans' => Plan::where('is_free', false)->orderBy('sort_order')->get(),
        ];
    }
}
