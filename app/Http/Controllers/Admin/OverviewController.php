<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contribution;
use App\Models\Cycle;
use App\Models\Member;
use App\Models\Payout;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;
        $from = today()->subDays($days - 1);
        $prevFrom = $from->copy()->subDays($days);

        $people = fn () => User::where('is_owner', false);
        $notOwner = fn ($q) => $q->where('is_owner', false);
        $monthStart = today()->startOfMonth();

        $newUsers = $people()->where('created_at', '>=', $from)->count();
        $prevNewUsers = $people()->whereBetween('created_at', [$prevFrom, $from])->count();

        $kpi = [
            'total_users' => $people()->count(),
            'new_users' => $newUsers,
            'new_users_trend' => $prevNewUsers > 0 ? round(($newUsers - $prevNewUsers) / $prevNewUsers * 100, 1) : null,
            'active_users' => $people()->where('last_seen_at', '>=', now()->subDays(30))->count(),
            'active_tontines' => Tontine::where('status', 'active')->whereHas('admin', $notOwner)->count(),
            'completed_tontines' => Tontine::where('status', 'completed')->whereHas('admin', $notOwner)->count(),
            'volume_month' => (float) Contribution::where('paid_at', '>=', $monthStart)
                ->whereHas('cycle.tontine.admin', $notOwner)->sum('amount'),
            'payouts_month' => (float) Payout::where('paid_at', '>=', $monthStart)
                ->whereHas('cycle.tontine.admin', $notOwner)
                ->selectRaw('coalesce(sum(amount - fee_amount), 0) as s')->value('s'),
        ];

        return view('admin.overview', [
            'days' => $days,
            'kpi' => $kpi,
            'charts' => $this->charts($days, $from),
            'funnel' => $this->funnel(),
            'late' => $this->lateStats(),
            'recent' => ActivityLog::with('user')->latest('id')->limit(8)->get(),
        ]);
    }

    /* ---------- Graphiques ---------- */

    private function charts(int $days, Carbon $from): array
    {
        $step = $days > 30 ? 7 : 1;

        $signups = User::where('is_owner', false)->where('created_at', '>=', $from)
            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd')->all();
        $tontines = Tontine::where('created_at', '>=', $from)
            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd')->all();
        $contribs = Contribution::where('paid_at', '>=', $from)
            ->selectRaw('date(paid_at) as d, sum(amount) as c')->groupBy('d')->pluck('c', 'd')->all();
        $payouts = Payout::where('paid_at', '>=', $from)
            ->selectRaw('date(paid_at) as d, sum(amount - fee_amount) as c')->groupBy('d')->pluck('c', 'd')->all();

        $labels = [];
        $out = ['signups' => [], 'tontines' => [], 'contribs' => [], 'payouts' => []];

        for ($d = $from->copy(); $d->lte(today()); $d->addDays($step)) {
            $end = $d->copy()->addDays($step - 1);
            $labels[] = $d->format('d/m');

            foreach (['signups' => $signups, 'tontines' => $tontines, 'contribs' => $contribs, 'payouts' => $payouts] as $key => $map) {
                $sum = 0;
                for ($x = $d->copy(); $x->lte($end) && $x->lte(today()); $x->addDay()) {
                    $sum += (float) ($map[$x->format('Y-m-d')] ?? 0);
                }
                $out[$key][] = $sum;
            }
        }

        return ['labels' => $labels] + $out;
    }

    /* ---------- Entonnoir d'activation (en nombre d'utilisateurs) ---------- */

    private function funnel(): array
    {
        $owners = fn ($q) => $q->where('is_owner', false);

        $counts = [
            User::where('is_owner', false)->count(),
            Tontine::whereHas('admin', $owners)->distinct()->count('admin_id'),
            Tontine::whereHas('admin', $owners)->has('members', '>=', 2)->distinct()->count('admin_id'),
            Tontine::whereHas('admin', $owners)->has('cycles')->distinct()->count('admin_id'),
            Tontine::whereHas('admin', $owners)->whereHas('cycles.contributions')->distinct()->count('admin_id'),
            Tontine::whereHas('admin', $owners)
                ->where(fn ($q) => $q->where('status', 'completed')->orWhere('current_round', '>', 1))
                ->distinct()->count('admin_id'),
        ];

        $labels = ['Enskri', 'Kreye tontin', 'Ajoute manm', 'Kalandriye', 'Peman', 'Fini yon sik'];
        $steps = [];
        $prev = $counts[0];

        foreach ($counts as $i => $count) {
            $count = min($count, $prev); // un entonnoir ne remonte jamais
            $steps[] = [
                'label' => $labels[$i],
                'count' => $count,
                'pct' => $counts[0] > 0 ? round($count / $counts[0] * 100) : 0,
                'drop' => $i > 0 && $prev > 0 ? round(($prev - $count) / $prev * 100) : null,
            ];
            $prev = $count;
        }

        return $steps;
    }

    /* ---------- Retards ---------- */

    private function lateStats(): array
    {
        $open = Cycle::select('cycles.*')
            ->addSelect(['members_total' => Member::selectRaw('count(*)')
                ->whereColumn('members.tontine_id', 'cycles.tontine_id')->where('members.is_active', true)])
            ->withCount('contributions')
            ->where('status', 'open')
            ->whereHas('tontine.admin', fn ($q) => $q->where('is_owner', false))
            ->get();

        $expected = (int) $open->sum('members_total');
        $buckets = ['1-3' => 0, '4-7' => 0, '8+' => 0];
        $unpaid = 0;

        foreach ($open as $c) {
            if (! $c->deadline->lt(today())) {
                continue;
            }

            $missing = max(0, (int) $c->members_total - (int) $c->contributions_count);
            $late = (int) $c->deadline->diffInDays(today());
            $unpaid += $missing;
            $buckets[$late <= 3 ? '1-3' : ($late <= 7 ? '4-7' : '8+')] += $missing;
        }

        return [
            'rate' => $expected > 0 ? round($unpaid / $expected * 100, 1) : 0.0,
            'unpaid' => $unpaid,
            'expected' => $expected,
            'buckets' => $buckets,
        ];
    }
}
