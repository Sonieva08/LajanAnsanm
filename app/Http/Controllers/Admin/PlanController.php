<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Setting;
use App\Support\Features;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::orderBy('sort_order')->orderBy('id')
                ->withCount(['subscriptions as subscribers_count' => fn ($q) => $q->where('status', 'active')->where('ends_at', '>=', now())])
                ->get(),
            'rules' => Setting::subscriptionRules(),
            'payment' => Setting::paymentInfo(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan(['is_active' => false, 'features' => [], 'sort_order' => (int) Plan::max('sort_order') + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $plan = Plan::create($data);
        ActivityLog::record('plan_updated', $request->user(), ['plan' => $plan->name]);

        return redirect()->route('admin.plans')->with('status', __('Plan kreye.'));
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', [
            'plan' => $plan,
            'subscribers' => $plan->subscriptions()->where('status', 'active')->where('ends_at', '>=', now())->count(),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validated($request, $plan);

        if ($plan->is_free) {
            $data['is_active'] = true;
            $data['price_monthly'] = 0;
            $data['price_quarterly'] = null;
            $data['price_yearly'] = null;
        }

        $plan->update($data);
        ActivityLog::record('plan_updated', $request->user(), ['plan' => $plan->name]);

        return redirect()->route('admin.plans')->with('status', __('Plan modifye.'));
    }

    public function toggle(Request $request, Plan $plan): RedirectResponse
    {
        if ($plan->is_free) {
            return back()->withErrors(['plan' => __('Plan gratis la pa ka dezaktive.')]);
        }

        if (! $plan->is_active && ($plan->priceFor('monthly') ?? 0) <= 0) {
            return back()->withErrors(['plan' => __('Fikse pri pa mwa a anvan ou aktive plan sa a.')]);
        }

        $plan->update(['is_active' => ! $plan->is_active]);
        ActivityLog::record('plan_updated', $request->user(), ['plan' => $plan->name]);

        return back()->with('status', $plan->is_active ? __('Plan aktive.') : __('Plan dezaktive.'));
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->is_free || $plan->subscriptions()->exists() || $plan->requests()->exists()) {
            return back()->withErrors(['plan' => __('Ou pa ka efase plan sa a : li gen abònman oswa demann ki makonnen ak li. Dezaktive li olye.')]);
        }

        $plan->delete();

        return redirect()->route('admin.plans')->with('status', __('Plan efase.'));
    }

    // Règles globales : délai de grâce et coordonnées de paiement
    public function rules(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grace_days' => ['required', 'integer', 'min:0', 'max:60'],
            'remind_days' => ['required', 'integer', 'min:0', 'max:30'],
            'moncash_number' => ['nullable', 'string', 'max:40'],
            'moncash_name' => ['nullable', 'string', 'max:80'],
            'natcash_number' => ['nullable', 'string', 'max:40'],
            'natcash_name' => ['nullable', 'string', 'max:80'],
            'bank_info' => ['nullable', 'string', 'max:300'],
            'instructions' => ['nullable', 'string', 'max:400'],
        ]);

        Setting::set('subscription', ['grace_days' => (int) $data['grace_days'], 'remind_days' => (int) $data['remind_days']]);
        Setting::set('payment', collect($data)->except(['grace_days', 'remind_days'])->all());

        return back()->with('status', __('Règ yo sove.'));
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:200'],
            'badge' => ['nullable', 'string', 'max:30'],
            'is_highlighted' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'price_monthly' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'price_quarterly' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'price_yearly' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'max_active_tontines' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'max_members_per_tontine' => ['nullable', 'integer', 'min:2', 'max:100000'],
            'unlimited_tontines' => ['nullable', 'boolean'],
            'unlimited_members' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::in(Features::keys())],
        ]);

        $isFree = $plan?->is_free ?? false;
        $active = $request->boolean('is_active');

        if (! $isFree && $active && (float) ($v['price_monthly'] ?? 0) <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['price_monthly' => __('Fikse pri pa mwa a anvan ou aktive plan sa a.')]);
        }

        if (! $request->boolean('unlimited_tontines') && empty($v['max_active_tontines'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['max_active_tontines' => __('Antre yon limit oswa make "Ilimite".')]);
        }

        if (! $request->boolean('unlimited_members') && empty($v['max_members_per_tontine'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['max_members_per_tontine' => __('Antre yon limit oswa make "Ilimite".')]);
        }

        return [
            'name' => $v['name'],
            'description' => $v['description'] ?? null,
            'badge' => $v['badge'] ?? null,
            'is_highlighted' => $request->boolean('is_highlighted'),
            'is_active' => $active,
            'sort_order' => (int) ($v['sort_order'] ?? 0),
            'price_monthly' => $v['price_monthly'] ?? null,
            'price_quarterly' => $v['price_quarterly'] ?? null,
            'price_yearly' => $v['price_yearly'] ?? null,
            'max_active_tontines' => $request->boolean('unlimited_tontines') ? null : (int) $v['max_active_tontines'],
            'max_members_per_tontine' => $request->boolean('unlimited_members') ? null : (int) $v['max_members_per_tontine'],
            'features' => array_values($v['features'] ?? []),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'plan';
        $slug = $base;

        for ($i = 2; Plan::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
