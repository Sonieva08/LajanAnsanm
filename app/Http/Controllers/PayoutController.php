<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\Member;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $transferRequired = $request->input('method') !== 'cash';

        $data = $request->validate([
            'confirm_incomplete' => ['nullable', 'boolean'],
            'method' => ['required', Rule::in(Member::PAYOUT_METHODS)],
            'account' => ['nullable', 'string', 'max:120', 'required_unless:method,cash'],
            'account_name' => ['nullable', 'string', 'max:100'],
            'sender_method' => [Rule::requiredIf($transferRequired), 'nullable', Rule::in(Member::PAYOUT_METHODS)],
            'sender_phone' => [Rule::requiredIf($transferRequired), 'nullable', 'string', 'max:40', 'regex:/^[0-9+\s\-]{6,}$/'],
            'transaction_ref' => [Rule::requiredIf($transferRequired), 'nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:300'],
            'receipt' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $result = DB::transaction(function () use ($request, $tontine, $cycle, $data) {
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

            // Où le bénéficiaire avait demandé à recevoir son tour (photographie : il peut changer ensuite)
            $beneficiary = Member::find($cycle->beneficiary_member_id);
            $transferRequired = $data['method'] !== 'cash';
            $receiptPath = $transferRequired && $request->hasFile('receipt')
                ? $request->file('receipt')->store('payout-receipts', 'local')
                : null;

            // Le montant et la commission sont toujours calculés ici, jamais envoyés par le navigateur
            $cycle->payout()->create([
                'method' => $data['method'],
                'account' => $data['method'] === 'cash' ? null : trim($data['account']),
                'account_name' => $data['method'] === 'cash' || blank($data['account_name'] ?? null) ? null : trim($data['account_name']),
                'member_id' => $cycle->beneficiary_member_id,
                'amount' => $collected,
                'fee_percent' => (float) $tontine->organizer_fee_percent,
                'fee_amount' => $tontine->feeFor($collected),
                'paid_at' => now()->toDateString(),
                'confirmed' => true,
                'sender_method' => $transferRequired ? $data['sender_method'] : null,
                'sender_phone' => $transferRequired ? trim($data['sender_phone']) : null,
                'transaction_ref' => $transferRequired ? trim($data['transaction_ref']) : null,
                'note' => $data['note'] ?? null,
                'receipt_path' => $receiptPath,
            ]);

            $cycle->update(['status' => 'closed']);

            // Ouvre le tour suivant ou attend la confirmation de tous les bénéficiaires avant de fermer le portail.
            $next = $tontine->roundCycles()
                ->where('cycle_number', '>', $cycle->cycle_number)
                ->where('status', 'pending')
                ->orderBy('cycle_number')
                ->first();

            if ($next) {
                $next->update(['status' => 'open']);
                return ['ok' => __('Vèsman fèt. Tou swivan an ouvè.')];
            }

            if (! $tontine->member_portal_enabled) {
                $tontine->update(['status' => 'completed']);

                return ['ok' => __('Vèsman fèt. Sòl la fini! 🎉')];
            }

            return ['ok' => __('Dènye vèsman an fèt. Sòl la ap fini lè tout benefisyè yo konfime yo resevwa lajan yo.')];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['payout' => $result['error']]);
        }

        return redirect()
            ->route('tontines.payouts.index', $tontine)
            ->with('status', $result['ok']);
    }

    public function receipt(Tontine $tontine, Cycle $cycle): StreamedResponse
    {
        $this->authorize('view', $tontine);

        abort_unless($cycle->tontine_id === $tontine->id, 404);
        $payout = $cycle->payout;
        abort_unless($payout?->receiptExists(), 404);

        return Storage::disk('local')->response($payout->receipt_path);
    }

    // Le bénéficiaire a dit « pas reçu » : le créateur renvoie l'argent puis rouvre la question pour qu'il puisse confirmer
    public function reopen(Tontine $tontine, Cycle $cycle): RedirectResponse
    {
        $this->authorize('update', $tontine);

        $payout = $cycle->payout;
        abort_unless($payout, 404);

        if (! $payout->isDisputed()) {
            return back()->withErrors(['payout' => __('Pa gen pwoblèm pou rezoud sou vèsman sa a.')]);
        }

        $payout->update(['member_response' => null, 'member_responded_at' => null, 'member_note' => null]);

        return redirect()->route('tontines.payouts.index', $tontine)->with('status', __('Nou mande manm nan konfime ankò.'));
    }
}
