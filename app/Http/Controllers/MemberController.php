<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MemberController extends Controller
{
    public function index(Tontine $tontine): View
    {
        $this->authorize('view', $tontine);

        $members = $tontine->members()->where('is_active', true)->get();
        $inactive = $tontine->members()->where('is_active', false)->where(function ($query) {
            $query->whereNull('invited_at')->orWhereNotNull('invitation_accepted_at');
        })->get();
        $pendingInvitations = $tontine->members()
            ->where('is_active', false)
            ->whereNotNull('invited_at')
            ->whereNull('invitation_accepted_at')
            ->get();

        $currentCycle = $tontine->cycles()->where('status', 'open')->first();
        $paidIds = $currentCycle
            ? $currentCycle->contributions()->pluck('member_id')->all()
            : [];

        return view('members.index', compact('tontine', 'members', 'inactive', 'pendingInvitations', 'currentCycle', 'paidIds'));
    }

    public function store(Tontine $tontine): RedirectResponse
    {
        $this->authorize('update', $tontine);

        if ($blocked = $this->blockedResponse($tontine)) {
            return $blocked;
        }

        $data = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s\-]+$/'],
        ]);

        request()->validate(
            ['consent' => ['accepted']],
            ['consent.accepted' => __('Ou dwe aksepte politik konfidansyalite a.')]
        );

        $data['position'] = ($tontine->members()->max('position') ?? 0) + 1;
        $data['consent_at'] = now();

        $member = $tontine->members()->create($data);

        if ($tontine->member_portal_enabled) {
            $member->issueAccessToken();
        }

        return back()->with('status', __('Manm ajoute.'));
    }

    public function invite(Request $request, Tontine $tontine): RedirectResponse
    {
        $this->authorize('update', $tontine);

        if ($blocked = $this->blockedResponse($tontine)) {
            return $blocked;
        }

        if (! $tontine->member_portal_enabled
            || (! $tontine->is_legacy && ! \App\Support\Entitlements::for($request->user())->has('member_portal'))) {
            return back()->withErrors(['invitation' => __('Ouvri pòtay manm yo anvan ou voye envitasyon.')]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30', 'regex:/^(?=(?:.*\d){6,})[0-9+\s\-]+$/'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'contact_permission' => ['accepted'],
        ], [
            'phone.required_without' => __('Bay yon nimewo telefòn oswa yon adrès imèl.'),
            'email.required_without' => __('Bay yon nimewo telefòn oswa yon adrès imèl.'),
            'contact_permission.accepted' => __('Konfime ou gen pèmisyon pou voye envitasyon sa a.'),
        ]);

        $member = DB::transaction(function () use ($tontine, $data) {
            $lockedTontine = Tontine::query()->whereKey($tontine->id)->lockForUpdate()->firstOrFail();

            if ($lockedTontine->isLocked() || $lockedTontine->status === 'completed'
                || ! $lockedTontine->member_portal_enabled
                || ! \App\Support\Entitlements::for($lockedTontine->admin)->canAddMember($lockedTontine)) {
                return null;
            }

            $member = $lockedTontine->members()->create([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'position' => null,
                'is_active' => false,
                'invited_at' => now(),
            ]);
            $member->issueAccessToken();

            return $member;
        });

        if (! $member) {
            return back()->withErrors(['invitation' => __('Sòl la deja kòmanse oswa li fini; li pa ka aksepte nouvo manm.')]);
        }

        $emailSent = false;
        if ($member->email) {
            try {
                Notification::route('mail', $member->email)->notify(
                    new \App\Notifications\TontineMemberInvitation(
                        $member->invitationUrl(),
                        $tontine->name,
                        $request->user()->name,
                        $member->name,
                    )
                );
                $emailSent = true;
            } catch (TransportExceptionInterface $exception) {
                Log::warning('Tontine invitation email could not be sent.', [
                    'member_id' => $member->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return back()->with([
            'status' => 'member-invitation-created',
            'invitation-email-status' => $member->email ? ($emailSent ? 'sent' : 'failed') : 'not-provided',
        ]);
    }

    public function update(Tontine $tontine, Member $member): RedirectResponse
    {
        $this->authorize('update', $tontine);

        $data = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s\-]+$/'],
        ]);

        $member->update($data);

        return back()->with('status', __('Manm modifye.'));
    }

    public function destroy(Tontine $tontine, Member $member): RedirectResponse
    {
        $this->authorize('update', $tontine);

        if ($blocked = $this->blockedResponse($tontine)) {
            return $blocked;
        }

        // Un membre qui a un historique n'est jamais supprimé (sinon ses paiements disparaîtraient)
        if ($member->contributions()->exists() || $member->payouts()->exists()) {
            $member->update(['is_active' => false, 'position' => null]);
            $message = __('Manm nan gen istwa peman, kidonk yo retire li olye yo efase li.');
        } else {
            $member->delete();
            $message = __('Manm efase.');
        }

        $this->applyOrder($tontine, $this->activeIds($tontine));

        return back()->with('status', $message);
    }

    public function reactivate(Tontine $tontine, Member $member): RedirectResponse
    {
        $this->authorize('update', $tontine);

        if ($blocked = $this->blockedResponse($tontine)) {
            return $blocked;
        }

        $member->update([
            'is_active' => true,
            'position' => ($tontine->members()->max('position') ?? 0) + 1,
        ]);

        return back()->with('status', __('Manm reaktive.'));
    }

    public function move(Tontine $tontine, Member $member, string $direction): RedirectResponse
    {
        $this->authorize('update', $tontine);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        if ($blocked = $this->blockedResponse($tontine)) {
            return $blocked;
        }

        $ids = $this->activeIds($tontine);
        $i = array_search($member->id, $ids, true);
        $j = $direction === 'up' ? $i - 1 : $i + 1;

        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            $this->applyOrder($tontine, $ids);
        }

        return back();
    }

    public function shuffle(Tontine $tontine): RedirectResponse
    {
        $this->authorize('update', $tontine);

        if ($blocked = $this->blockedResponse($tontine)) {
            return $blocked;
        }

        $ids = $this->activeIds($tontine);
        shuffle($ids);
        $this->applyOrder($tontine, $ids);

        return back()->with('status', __('Tiraj fèt. Nouvo lòd la anrejistre.'));
    }

    private function activeIds(Tontine $tontine): array
    {
        return $tontine->members()->where('is_active', true)->pluck('id')->all();
    }

    private function blockedResponse(Tontine $tontine): ?RedirectResponse
    {
        if ($tontine->isLocked()) {
            return back()->withErrors(['locked' => __('Sòl la deja kòmanse. Ou pa ka chanje lis manm yo ankò.')]);
        }

        return null;
    }

    // Deux passes pour ne pas violer unique(tontine_id, position)
    private function applyOrder(Tontine $tontine, array $orderedIds): void
    {
        DB::transaction(function () use ($tontine, $orderedIds) {
            Member::where('tontine_id', $tontine->id)->update(['position' => null]);

            foreach ($orderedIds as $index => $id) {
                Member::where('tontine_id', $tontine->id)
                    ->where('id', $id)
                    ->update(['position' => $index + 1]);
            }
        });
    }
}
