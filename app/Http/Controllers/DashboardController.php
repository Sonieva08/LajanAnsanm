<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $userId = Auth::id();

        $tontines = Auth::user()->tontines()
            ->withCount(['cycles as cycles_count' => fn ($q) => $q->whereColumn('cycles.round', 'tontines.current_round')])
        ->withCount(['members as active_members_count' => fn ($q) => $q->where('is_active', true)])
    
            ->withCount(['members as active_members_count' => fn ($q) => $q->where('is_active', true)])
            ->with(['cycles' => fn ($q) => $q
                ->where('status', 'open')
                ->with('beneficiary')
                ->withCount('contributions')
                ->withSum('contributions', 'amount'),
            ])
            ->latest()
            ->get();

        // Alertes : tours ouverts dont la date limite est dépassée
        $alerts = [];
        foreach ($tontines as $tontine) {
            $cycle = $tontine->cycles->first();

            if ($cycle && $cycle->isLate()) {
                $alerts[] = [
                    'tontine' => $tontine,
                    'cycle' => $cycle,
                    'unpaid' => $tontine->members()
                        ->where('is_active', true)
                        ->whereNotIn('id', $cycle->contributions()->select('member_id'))
                        ->get(),
                ];
            }
        }

        $active = $tontines->where('status', 'active');

        $stats = [
            'active_tontines' => $active->count(),
            'members' => $active->sum('active_members_count'),
            'collected' => (float) Contribution::whereHas(
                'cycle.tontine',
                fn ($q) => $q->where('admin_id', $userId)
            )->sum('amount'),
            'late' => count($alerts),
        ];

        return view('dashboard', compact('tontines', 'alerts', 'stats'));
    }
}