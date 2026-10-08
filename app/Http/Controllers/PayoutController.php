<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(Tontine $tontine): View
    {
        $this->authorize('view', $tontine);

        $cycles = $tontine->roundCycles()
            ->with(['beneficiary', 'payout'])
            ->withCount('contributions')
            ->withSum('contributions', 'amount')
            ->get();

        return view('payouts.index', [
            'tontine' => $tontine,
            'cycles' => $cycles,
            'activeMembers' => $tontine->activeMembersCount(),
            'totalPaidOut' => $cycles->sum(fn ($c) => (float) ($c->payout?->netAmount() ?? 0)),
            'totalFees' => $cycles->sum(fn ($c) => (float) ($c->payout?->fee_amount ?? 0)),
        ]);
    }

    public function store(Request $request, Tontine $tontine, Cycle $cycle): RedirectResponse
    {
        $this->authorize('update', $tontine);

        $request->validate([
            'confirm_incomplete' => ['nullable', 'boolean'],
        ]);

        $result = DB::transaction(function () use ($request, $tontine, $cycle) {
            // Verrou pour éviter un double clic ou deux onglets
            $cycle = Cycle::whereKey($cycle->id)->lockForUpdate()->first();

            if ($cycle->status !== 'open') {
                return ['error' => __('Tour sa a pa ouvè.')];
            }

            if ($cycle->payout()->exists()) {
                return ['error' => __('Benefisyè a deja resevwa lajan pou tou sa a.')];
            }

            if (! $cycle->beneficiary_member_id) {
                return ['error' => __('Pa gen benefisyè pou tou sa a.')];
            }

            $collected = (float) $cycle->contributions()->sum('amount');
            $paidCount = $cycle->contributions()->count();
            $expected = $tontine->activeMembersCount();

            if ($paidCount === 0 || $collected <= 0) {
                return ['error' => __('Pa gen okenn peman pou tou sa a. Pa gen anyen pou vèse.')];
            }

            if ($paidCount < $expected && ! $request->boolean('confirm_incomplete')) {
                return ['error' => __('Gen manm ki poko peye. Make kaz la pou konfime ou vle vèse kanmenm.')];
            }

            // Le montant et la commission sont toujours calculés ici, jamais envoyés par le navigateur
            $cycle->payout()->create([
                'member_id' => $cycle->beneficiary_member_id,
                'amount' => $collected,
                'fee_percent' => (float) $tontine->organizer_fee_percent,
                'fee_amount' => $tontine->feeFor($collected),
                'paid_at' => now()->toDateString(),
                'confirmed' => true,
            ]);

            $cycle->update(['status' => 'closed']);

            // Ouvre le tour suivant, ou termine la tontine
            $next = $tontine->roundCycles()
                ->where('cycle_number', '>', $cycle->cycle_number)
                ->where('status', 'pending')
                ->orderBy('cycle_number')
                ->first();

            if ($next) {
                $next->update(['status' => 'open']);
                return ['ok' => __('Vèsman fèt. Tou swivan an ouvè.')];
            }

            $tontine->update(['status' => 'completed']);
            return ['ok' => __('Vèsman fèt. Tontine a fini! 🎉')];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['payout' => $result['error']]);
        }

        return redirect()
            ->route('tontines.payouts.index', $tontine)
            ->with('status', $result['ok']);
    }
}
