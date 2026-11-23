<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Member;
use App\Models\Tontine;
use App\Support\Entitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function create(Request $request, Tontine $tontine): View
    {
        $this->authorize('update', $tontine);

        $entitlements = Entitlements::for($tontine->admin);

        return view('invitations.create', [
            'tontine' => $tontine,
            'hasFeature' => $entitlements->has('member_portal') && $tontine->member_portal_enabled,
            'canAdd' => $entitlements->canAddMember($tontine),
            'memberCount' => $tontine->members()->where('is_active', true)->count(),
            'maxMembers' => $entitlements->maxMembers(),
            'days' => Invitation::DAYS,
        ]);
    }

    public function store(Request $request, Tontine $tontine): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $tontine);

        $ent = Entitlements::for($tontine->admin);

        if (! $ent->has('member_portal')) {
            return redirect()->route('plans')->with('limit', ['need' => 'member_portal', 'used' => null, 'max' => null]);
        }

        if (! $tontine->member_portal_enabled) {
            return redirect()->route('invitations.create', $tontine)
                ->withErrors(['invite' => __('Ouvri pòtay manm yo anvan ou voye envitasyon.')]);
        }

        if ($tontine->status !== 'draft' || $tontine->isLocked()) {
            return redirect()->route('invitations.create', $tontine)
                ->withErrors(['invite' => __('Kalandriye a deja lanse. Ou pa ka ajoute moun ankò.')]);
        }

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:.*\d){6,})[0-9+\s\-]+$/'],
            'contact_permission' => ['accepted'],
        ], [
            'contact_permission.accepted' => __('Konfime ou gen pèmisyon pou voye envitasyon sa a.'),
        ]);

        $issued = DB::transaction(function () use ($tontine, $user, $data) {
            $lockedTontine = Tontine::query()->with('admin')->whereKey($tontine->id)->lockForUpdate()->firstOrFail();

            if ($lockedTontine->status !== 'draft' || $lockedTontine->isLocked()
                || ! $lockedTontine->member_portal_enabled
                || ! Entitlements::for($lockedTontine->admin)->canAddMember($lockedTontine)) {
                return null;
            }

            return Invitation::issue($lockedTontine, $user, $data['phone']);
        });

        if (! $issued) {
            return redirect()->route('invitations.create', $tontine)
                ->withErrors(['invite' => __('Ou rive nan limit manm pou plan ou an, oswa sòl la pa ka resevwa nouvo envitasyon.')]);
        }

        [, $plain] = $issued;

        $url = route('invitations.show', $plain);
        $digits = preg_replace('/\D/', '', (string) ($data['phone'] ?? ''));
        if (strlen($digits) === 8) {
            $digits = '509'.$digits;
        }
        $message = __('Ou envite w rejwenn sòl :t sou LajanAnsanm: :u', ['t' => $tontine->name, 'u' => $url]);

        return redirect()->route('invitations.create', $tontine)
            ->with('invite_url', $url)
            ->with('invite_wa', 'https://wa.me/'.$digits.'?text='.rawurlencode($message))
            ->with('invite_sms', 'sms:'.($data['phone'] ?? '').'?body='.rawurlencode($message));
    }

    public function show(string $token): Response|View
    {
        $invitation = Invitation::findByToken($token);

        if (! $invitation || ! $invitation->isUsable()) {
            return response()->view('invitations.invalid', [], 404);
        }

        $invitation->load('tontine.admin');
        $alreadyMember = auth()->check() && Member::where('tontine_id', $invitation->tontine_id)
            ->where('user_id', auth()->id())->where('is_active', true)->exists();

        return view('invitations.show', [
            'invitation' => $invitation,
            'token' => $token,
            'organizer' => $invitation->tontine->admin?->name,
            'alreadyMember' => $alreadyMember,
            'memberName' => auth()->user()?->name,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $request->validate(
            [
                'name' => ['required', 'string', 'max:120'],
                'consent' => ['accepted'],
            ],
            ['consent.accepted' => __('Ou dwe aksepte pou yo konsève enfòmasyon manm ou yo pou jere sòl la.')]
        );

        $user = $request->user();
        $name = trim($request->string('name')->toString());

        $result = DB::transaction(function () use ($token, $user, $name) {
            $invitation = Invitation::where('token_hash', hash('sha256', $token))->lockForUpdate()->first();

            if (! $invitation || ! $invitation->isUsable()) {
                return ['invalid'];
            }

            $tontine = Tontine::with('admin')->whereKey($invitation->tontine_id)->lockForUpdate()->first();

            if (! $tontine || $tontine->status !== 'draft' || $tontine->isLocked()) {
                return ['started'];
            }

            if (! $tontine->member_portal_enabled
                || ! Entitlements::for($tontine->admin)->has('member_portal')) {
                return ['portal-disabled'];
            }

            $member = $user
                ? $tontine->members()->where('user_id', $user->id)->where('is_active', true)->first()
                : null;

            if (! $member && ! Entitlements::for($tontine->admin)->canAddMember($tontine, $invitation->id)) {
                return ['limit'];
            }

            if (! $member) {
                $member = new Member;
                $member->forceFill([
                    'tontine_id' => $tontine->id,
                    'user_id' => $user?->id,
                    'name' => $name,
                    'phone' => $invitation->phone,
                    'position' => ($tontine->members()->max('position') ?? 0) + 1,
                    'is_active' => true,
                    'consent_at' => now(),
                ])->save();
            }

            if (! $member->access_token_hash) {
                $portalToken = $member->issueAccessToken();
            } else {
                $portalToken = $member->access_token;
            }
            $invitation->update(['accepted_at' => now(), 'accepted_by' => $user?->id]);

            return ['ok', $portalToken];
        });

        if ($result[0] === 'ok') {
            return redirect()->route('invitations.joined', $result[1]);
        }

        $messages = [
            'started' => __('Sòl sa a deja kòmanse. Ou pa ka rantre ankò.'),
            'portal-disabled' => __('Pòtay manm sa a pa disponib ankò.'),
            'limit' => __('Òganizatè a rive nan limit manm pou plan li an.'),
            'invalid' => __('Envitasyon sa a pa valab ankò.'),
        ];

        return redirect()->route('invitations.show', $token)
            ->withErrors(['invite' => $messages[$result[0]] ?? __('Envitasyon sa a pa valab ankò.')]);
    }

    public function joined(string $token): Response|View
    {
        $member = Member::findByAccessToken($token);

        if (! $member?->is_active || ! $member->tontine->member_portal_enabled
            || $member->tontine->status === 'completed') {
            return response()->view('portal.invalid', [], 404);
        }

        return view('invitations.joined', [
            'member' => $member,
            'portalUrl' => route('portal.enter', $token),
        ]);
    }

    public function mine(Request $request): View
    {
        return view('invitations.mine', [
            'memberships' => Member::with('tontine')->where('user_id', $request->user()->id)
                ->where('is_active', true)->get(),
        ]);
    }
}
