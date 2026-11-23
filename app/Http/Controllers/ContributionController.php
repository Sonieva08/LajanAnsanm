<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Cycle;
use App\Models\PaymentDeclaration;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContributionController extends Controller
{
    public const METHODS = ['cash', 'moncash', 'natcash', 'bank'];

    public function index(Request $request, Tontine $tontine): View|RedirectResponse
    {
        $this->authorize('view', $tontine);

        if (! $tontine->isLocked()) {
            return redirect()
                ->route('tontines.cycles.index', $tontine)
                ->withErrors(['cycles' => __('Jenere tou yo anvan ou anrejistre peman.')]);
        }

        $cycles = $tontine->roundCycles()->get();

        // Tour demandé, sinon le tour ouvert, sinon le dernier
        $cycle = $request->filled('cycle')
            ? $cycles->firstWhere('id', (int) $request->query('cycle'))
            : null;

        $cycle ??= $cycles->firstWhere('status', 'open') ?? $cycles->last();

        $cycle->load('beneficiary');

        $members = $tontine->members()->where('is_active', true)->get();
        $contributions = $cycle->contributions()->get()->keyBy('member_id');

        return view('contributions.index', [
            'tontine' => $tontine,
            'cycles' => $cycles,
            'cycle' => $cycle,
            'members' => $members,
            'contributions' => $contributions,
            'progress' => $cycle->paymentProgress(),
            'methods' => self::METHODS,
        ]);
    }

    public function store(Request $request, Tontine $tontine, Cycle $cycle): RedirectResponse
    {
        $this->authorize('update', $tontine);

        $data = $request->validate([
            'member_id' => [
                'required',
                Rule::exists('members', 'id')
                    ->where('tontine_id', $tontine->id)
                    ->where('is_active', true),
            ],
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use ($cycle, $tontine, $data) {
            $lockedCycle = Cycle::whereKey($cycle->id)->lockForUpdate()->firstOrFail();

            if ($lockedCycle->status !== 'open') {
                return ['error' => __('Tour sa a pa ouvè.')];
            }

            if ($lockedCycle->contributions()->where('member_id', $data['member_id'])->exists()) {
                return ['error' => __('Manm sa a deja peye pou tou sa a.')];
            }

            $contribution = $lockedCycle->contributions()->create($data + ['amount' => $tontine->amount_per_period]);

            PaymentDeclaration::where('cycle_id', $lockedCycle->id)->where('member_id', $data['member_id'])->where('status', 'pending')
                ->update(['status' => 'approved', 'reviewed_at' => now(), 'contribution_id' => $contribution->id]);

            return ['ok' => true];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['contribution' => $result['error']]);
        }

        return redirect()
            ->route('tontines.contributions.index', [$tontine, 'cycle' => $cycle->id])
            ->with('status', __('Peman anrejistre.'));
    }

    public function destroy(Tontine $tontine, Cycle $cycle, Contribution $contribution): RedirectResponse
    {
        $this->authorize('update', $tontine);

        $result = DB::transaction(function () use ($cycle, $contribution) {
            $lockedCycle = Cycle::whereKey($cycle->id)->lockForUpdate()->firstOrFail();

            if ($lockedCycle->status !== 'open') {
                return ['error' => __('Tour sa a pa ouvè.')];
            }

            $lockedCycle->contributions()->whereKey($contribution->id)->firstOrFail()->delete();

            return ['ok' => true];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['contribution' => $result['error']]);
        }

        return redirect()
            ->route('tontines.contributions.index', [$tontine, 'cycle' => $cycle->id])
            ->with('status', __('Peman anile.'));
    }
}