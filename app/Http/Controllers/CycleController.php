<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CycleController extends Controller
{
    public function index(Tontine $tontine): View
    {
        $this->authorize('view', $tontine);

        $cycles = $tontine->roundCycles()
            ->with('beneficiary')
            ->withCount('contributions')
            ->get();

        // Historique des kòl précédents
        $history = $tontine->cycles()
            ->where('round', '<', $tontine->current_round)
            ->with('payout')
            ->get()
            ->groupBy('round')
            ->map(fn ($g) => [
                'cycles' => $g->count(),
                'paid_out' => $g->sum(fn ($c) => (float) ($c->payout?->amount ?? 0)),
                'from' => $g->first()->deadline,
                'to' => $g->last()->deadline,
            ]);

        // Date de départ suggérée pour un nouveau kòl
        $suggestedStart = today();
        if ($last = $cycles->last()) {
            $next = $tontine->period_type === 'weekly'
                ? $last->deadline->copy()->addWeek()
                : $last->deadline->copy()->addMonthNoOverflow();
            $suggestedStart = $next->lt(today()) ? today() : $next;
        }

        return view('cycles.index', [
            'tontine' => $tontine,
            'cycles' => $cycles,
            'history' => $history,
            'suggestedStart' => $suggestedStart->format('Y-m-d'),
            'activeMembers' => $tontine->activeMembersCount(),
        ]);
    }

    public function generate(Tontine $tontine): RedirectResponse
    {
        $this->authorize('update', $tontine);

        if ($tontine->isLocked()) {
            return back()->withErrors(['cycles' => __('Tour yo deja jenere.')]);
        }

        $members = $tontine->members()->where('is_active', true)->get();

        if ($members->count() < 2) {
            return back()->withErrors(['cycles' => __('Ou bezwen omwen 2 manm pou kòmanse tontine a.')]);
        }

        DB::transaction(function () use ($tontine, $members) {
            foreach ($members->values() as $i => $member) {
                $deadline = $tontine->start_date->copy()->startOfDay();

                $deadline = $tontine->period_type === 'weekly'
                    ? $deadline->addWeeks($i)
                    : $deadline->addMonthsNoOverflow($i);

                Cycle::create([
                    'tontine_id' => $tontine->id,
                    'round' => $tontine->current_round,
                    'cycle_number' => $i + 1,
                    'beneficiary_member_id' => $member->id,
                    'deadline' => $deadline,
                    'status' => $i === 0 ? 'open' : 'pending',
                ]);
            }

            $tontine->update(['status' => 'active']);
        });

        return redirect()
            ->route('tontines.cycles.index', $tontine)
            ->with('status', __('Tour yo jenere. Tontine a kòmanse!'));
    }

    public function reset(Tontine $tontine): RedirectResponse
    {
        $this->authorize('update', $tontine);

        if ($tontine->status === 'completed') {
            return back()->withErrors(['cycles' => __('Kòl sa a fini. Ou pa ka anile li.')]);
        }

        if ($tontine->roundContributions()->exists()) {
            return back()->withErrors([
                'cycles' => __('Gen peman ki deja anrejistre. Ou pa ka anile kalandriye a.'),
            ]);
        }

        DB::transaction(function () use ($tontine) {
            $tontine->roundCycles()->delete();
            $tontine->update(['status' => 'draft']);
        });

        return redirect()
            ->route('tontines.cycles.index', $tontine)
            ->with('status', __('Kalandriye anile. Ou ka modifye manm yo ankò.'));
    }

    // Relance un nouveau kòl après la fin de tous les tours
    public function restart(Request $request, Tontine $tontine): RedirectResponse
    {
        $this->authorize('update', $tontine);

        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'shuffle' => ['nullable', 'boolean'],
        ]);

        $done = DB::transaction(function () use ($tontine, $data, $request) {
            // Verrou : évite un double clic ou deux onglets
            $t = Tontine::whereKey($tontine->id)->lockForUpdate()->first();

            if ($t->status !== 'completed') {
                return false;
            }

            $t->update([
                'current_round' => $t->current_round + 1,
                'status' => 'draft',
                'start_date' => $data['start_date'],
            ]);

            if ($request->boolean('shuffle')) {
                $t->shuffleMembers();
            }

            return true;
        });

        if (! $done) {
            return back()->withErrors(['cycles' => __('Tontine a poko fini. Ou pa ka relanse li.')]);
        }

        return redirect()
            ->route('tontines.members.index', $tontine)
            ->with('status', __('Nouvo kòl prepare. Ajoute oswa modifye manm yo, answit jenere tou yo.'));
    }
}
