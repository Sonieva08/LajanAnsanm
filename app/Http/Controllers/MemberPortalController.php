<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Cycle;
use App\Models\Member;
use App\Models\PaymentDeclaration;
use App\Models\Payout;
use App\Models\Tontine;
use App\Notifications\PaymentDeclarationSubmitted;
use App\Notifications\PayoutDisputed;
use App\Notifications\PayoutReceivedConfirmed;
use App\Support\Entitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Espace du membre : il voit le calendrier et l'état de SES cotisations, jamais celles des autres ni leurs téléphones
class MemberPortalController extends Controller
{
    public function enter(Request $request, string $token): RedirectResponse|Response
    {
        $member = Member::findByAccessToken($token);

        if (! $member || ! $member->is_active || ! $member->tontine->member_portal_enabled
            || $member->tontine->status === 'completed') {
            return response()->view('portal.invalid', [], 404);
        }

        return $this->startPortalSession($request, $member);
    }

    public function enterForAccount(Request $request, Member $member): RedirectResponse|Response
    {
        $member->loadMissing('tontine');

        if ((int) $member->user_id !== (int) $request->user()->id
            || ! $member->is_active
            || ! $member->tontine->member_portal_enabled
            || $member->tontine->status === 'completed'
            || ! ($member->tontine->is_legacy || Entitlements::for($member->tontine->admin)->has('member_portal'))) {
            return response()->view('portal.invalid', [], 404);
        }

        if (! $member->access_token_hash) {
            $member->issueAccessToken();
        }

        return $this->startPortalSession($request, $member);
    }

    private function startPortalSession(Request $request, Member $member): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put([
            'portal_member_id' => $member->id,
            'portal_token_hash' => $member->access_token_hash,
            'security.authenticated_at' => now()->timestamp,
        ]);

        $member->forceFill(['last_portal_visit_at' => now()])->save();

        return redirect()->route('portal.home');
    }

    public function home(Request $request): View
    {
        /** @var Member $member */
        $member = $request->attributes->get('portal_member');
        $tontine = $member->tontine;

        $cycles = $tontine->roundCycles()->with('beneficiary:id,name')->get();
        $mine = Contribution::where('member_id', $member->id)->whereIn('cycle_id', $cycles->pluck('id'))->get()->keyBy('cycle_id');

        $openCycle = $cycles->firstWhere('status', 'open');
        $myTurn = $cycles->firstWhere('beneficiary_member_id', $member->id);

        // Mon versement (s'il a été fait) : je dois confirmer que je l'ai reçu
        $myPayout = Payout::where('member_id', $member->id)->whereIn('cycle_id', $cycles->pluck('id'))->latest('id')->first();

        $declaration = $openCycle
            ? PaymentDeclaration::where('member_id', $member->id)->where('cycle_id', $openCycle->id)->latest('id')->first()
            : null;

        return view('portal.home', [
            'member' => $member,
            'tontine' => $tontine,
            'cycles' => $cycles,
            'mine' => $mine,
            'openCycle' => $openCycle,
            'dueNow' => $openCycle && ! $mine->has($openCycle->id),
            'myTurn' => $myTurn,
            'myPayout' => $myPayout,
            'pending' => $declaration?->status === 'pending' ? $declaration : null,
            'rejected' => $declaration?->status === 'rejected' ? $declaration : null,
            'totalPaid' => (float) $mine->sum('amount'),
            'payment' => $tontine->portalPayment(),
            'methods' => $tontine->portalPaymentMethods(),
        ]);
    }

    // Le membre déclare qu'il a payé ; rien n'est compté tant que le créateur n'a pas validé
    public function declare(Request $request, Cycle $cycle): RedirectResponse
    {
        /** @var Member $member */
        $member = $request->attributes->get('portal_member');
        $tontine = $member->tontine;

        abort_unless($cycle->tontine_id === $tontine->id, 404);

        // Tontine en lecture seule chez le créateur : aucune nouvelle déclaration
        abort_unless(Entitlements::for($tontine->admin)->isWritable($tontine), 403);

        $methods = $tontine->portalPaymentMethods();
        $paymentMethod = $request->input('payment_method');
        $transactionIdRequired = in_array($paymentMethod, ['moncash', 'natcash'], true);

        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys($methods))],
            'payer_phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\s\-]{6,}$/'],
            'transaction_ref' => [Rule::requiredIf($transactionIdRequired), 'nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:300'],
            'receipt' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $ref = trim((string) ($data['transaction_ref'] ?? ''));

        $result = DB::transaction(function () use ($cycle, $member, $tontine, $data, $ref, $request) {
            $cycle = Cycle::whereKey($cycle->id)->lockForUpdate()->first();

            if ($cycle->status !== 'open') {
                return ['error' => __('Tou sa a pa ouvè.')];
            }

            if ($cycle->contributions()->where('member_id', $member->id)->exists()) {
                return ['error' => __('Peman ou an deja anrejistre.')];
            }

            $mine = PaymentDeclaration::where('cycle_id', $cycle->id)->where('member_id', $member->id);

            if ((clone $mine)->where('status', 'pending')->exists()) {
                return ['error' => __('Ou gen yon deklarasyon ki ap tann konfimasyon deja.')];
            }

            $previous = (clone $mine)->where('status', 'rejected')->latest('id')->first();

            $dup = $ref !== '' && PaymentDeclaration::where('payment_method', $data['payment_method'])
                ->whereRaw('lower(transaction_ref) = ?', [mb_strtolower($ref)])
                ->whereIn('status', ['pending', 'approved'])
                ->when($previous, fn ($q) => $q->where('id', '!=', $previous->id))
                ->exists();

            if ($dup) {
                return ['error' => __('Nimewo tranzaksyon sa a deja itilize.')];
            }

            $receiptPath = $request->file('receipt')->store('receipts', 'local');
            if (! $receiptPath) {
                throw new \RuntimeException('Unable to store payment receipt.');
            }

            $fields = [
                'amount' => $tontine->amount_per_period,
                'payment_method' => $data['payment_method'],
                'payer_phone' => $data['payer_phone'] ?? null,
                'transaction_ref' => $ref !== '' ? $ref : null,
                'note' => $data['note'] ?? null,
                'status' => 'pending',
                'reviewed_at' => null,
                'review_note' => null,
            ];

            if ($previous?->receipt_path && Storage::disk('local')->exists($previous->receipt_path)) {
                Storage::disk('local')->delete($previous->receipt_path);
            }
            $fields['receipt_path'] = $receiptPath;

            // Après un refus, le membre corrige et renvoie : on réutilise la même ligne
            if ($previous) {
                $previous->update($fields);
                $declaration = $previous;
            } else {
                $declaration = PaymentDeclaration::create($fields + ['cycle_id' => $cycle->id, 'member_id' => $member->id]);
            }

            // Notifier l'organisateur de la tontine
            $tontine->admin?->notify(new PaymentDeclarationSubmitted($declaration));

            return ['ok' => __('Nou resevwa deklarasyon ou an. Òganizatè a ap verifye li.')];
        });

        return isset($result['error'])
            ? redirect()->route('portal.home')->withErrors(['declaration' => $result['error']])->withInput()
            : redirect()->route('portal.home')->with('status', $result['ok']);
    }

    public function receipt(Request $request, PaymentDeclaration $declaration)
    {
        /** @var Member $member */
        $member = $request->attributes->get('portal_member');

        abort_unless($declaration->member_id === $member->id && $declaration->receipt_path, 404);
        abort_unless(Storage::disk('local')->exists($declaration->receipt_path), 404);

        return Storage::disk('local')->response($declaration->receipt_path);
    }

    public function payoutReceipt(Request $request, Payout $payout): StreamedResponse
    {
        /** @var Member $member */
        $member = $request->attributes->get('portal_member');

        abort_unless($payout->member_id === $member->id && $payout->receiptExists(), 404);

        return Storage::disk('local')->response($payout->receipt_path);
    }

    // Comment je veux recevoir mon tour
    public function payoutDestination(Request $request): RedirectResponse
    {
        /** @var Member $member */
        $member = $request->attributes->get('portal_member');

        $data = $request->validate([
            'payout_method' => ['required', Rule::in(Member::PAYOUT_METHODS)],
            'payout_account' => ['nullable', 'string', 'max:120', 'required_unless:payout_method,cash'],
            'payout_name' => ['nullable', 'string', 'max:100'],
        ]);

        $cash = $data['payout_method'] === 'cash';

        $member->update([
            'payout_method' => $data['payout_method'],
            'payout_account' => $cash ? null : trim($data['payout_account']),
            'payout_name' => $cash || blank($data['payout_name'] ?? null) ? null : trim($data['payout_name']),
        ]);

        return redirect()->route('portal.home')->with('status', __('Nou sove kijan ou vle resevwa lajan ou.'));
    }

    // « J'ai bien reçu l'argent »
    public function confirmPayout(Request $request, Payout $payout): RedirectResponse
    {
        $member = $this->ownPayoutMember($request, $payout);

        if ($payout->isReceived()) {
            return redirect()->route('portal.home');
        }

        $confirmed = DB::transaction(function () use ($payout) {
            $lockedPayout = Payout::whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayout->isReceived()) {
                return false;
            }

            $lockedPayout->update(['member_response' => 'received', 'member_responded_at' => now(), 'member_note' => null]);

            $tontineId = $lockedPayout->cycle()->value('tontine_id');
            $tontine = Tontine::whereKey($tontineId)->lockForUpdate()->firstOrFail();

            if (! $tontine->member_portal_enabled || $tontine->status !== 'active') {
                return true;
            }

            $hasUnconfirmedPayouts = $tontine->roundCycles()
                ->whereDoesntHave('payout', fn ($query) => $query->where('member_response', 'received'))
                ->exists();

            if (! $hasUnconfirmedPayouts) {
                $tontine->update(['status' => 'completed']);
            }

            return true;
        });

        if (! $confirmed) {
            return redirect()->route('portal.home');
        }

        $payout->refresh();

        $payout->cycle?->tontine?->admin?->notify(new PayoutReceivedConfirmed($payout));

        return redirect()->route('portal.home')->with('status', __('Mèsi! Ou konfime ou resevwa lajan an.'));
    }

    // « Je n'ai pas reçu l'argent » : le créateur le voit tout de suite
    public function disputePayout(Request $request, Payout $payout): RedirectResponse
    {
        $this->ownPayoutMember($request, $payout);

        $data = $request->validate(['member_note' => ['required', 'string', 'max:300']]);

        if ($payout->isReceived()) {
            return redirect()->route('portal.home')->withErrors(['payout' => __('Ou deja konfime ou resevwa lajan an.')]);
        }

        $payout->update(['member_response' => 'not_received', 'member_responded_at' => now(), 'member_note' => trim($data['member_note'])]);

        $payout->cycle?->tontine?->admin?->notify(new PayoutDisputed($payout));

        return redirect()->route('portal.home')->with('status', __('Nou notifye òganizatè a.'));
    }

    // Un membre ne répond que pour SON versement
    private function ownPayoutMember(Request $request, Payout $payout): Member
    {
        /** @var Member $member */
        $member = $request->attributes->get('portal_member');
        abort_unless($payout->member_id === $member->id, 404);

        return $member;
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['portal_member_id', 'portal_token_hash']);

        return redirect('/');
    }
}
