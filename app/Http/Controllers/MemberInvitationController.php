<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Tontine;
use App\Support\Entitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MemberInvitationController extends Controller
{
    public function show(string $token): View|RedirectResponse|Response
    {
        $member = Member::findByAccessToken($token);

        if ($member?->is_active && $member->invitation_accepted_at) {
            return redirect()->route('portal.enter', $token);
        }

        if (! $this->isPendingInvitation($member)) {
            return response()->view('portal.invalid', [], 404);
        }

        return response()->view('invitations.accept', compact('member', 'token'));
    }

    public function accept(Request $request, string $token): RedirectResponse|Response
    {
        $request->validate(
            ['consent' => ['accepted']],
            ['consent.accepted' => __('Ou dwe aksepte politik konfidansyalite a.')],
        );

        $member = Member::findByAccessToken($token);
        if (! $this->isPendingInvitation($member)) {
            return response()->view('portal.invalid', [], 404);
        }

        $accepted = DB::transaction(function () use ($member): bool {
            $tontine = Tontine::query()->whereKey($member->tontine_id)->lockForUpdate()->firstOrFail();
            $lockedMember = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if ($tontine->status === 'completed' || ! $tontine->member_portal_enabled || $tontine->isLocked()
                || $lockedMember->is_active || ! $lockedMember->invited_at || $lockedMember->invitation_accepted_at) {
                return false;
            }

            $entitlements = Entitlements::for($tontine->admin);
            if (! $tontine->is_legacy && ! $entitlements->has('member_portal')) {
                return false;
            }

            $maxMembers = $entitlements->maxMembers();
            if (! $tontine->is_legacy && $maxMembers !== null
                && $tontine->members()->where('is_active', true)->count() >= $maxMembers) {
                return false;
            }

            $lockedMember->forceFill([
                'is_active' => true,
                'position' => ($tontine->members()->max('position') ?? 0) + 1,
                'consent_at' => now(),
                'invitation_accepted_at' => now(),
            ])->save();

            return true;
        });

        if (! $accepted) {
            return response()->view('portal.invalid', [], 404);
        }

        return redirect()->route('portal.enter', $token);
    }

    private function isPendingInvitation(?Member $member): bool
    {
        return $member !== null
            && ! $member->is_active
            && $member->invited_at !== null
            && $member->invitation_accepted_at === null
            && $member->tontine->status !== 'completed'
            && ! $member->tontine->isLocked()
            && $member->tontine->member_portal_enabled
            && ($member->tontine->is_legacy || Entitlements::for($member->tontine->admin)->has('member_portal'));
    }
}
