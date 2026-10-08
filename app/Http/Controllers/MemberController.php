<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Tontine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Tontine $tontine): View
    {
        $this->authorize('view', $tontine);

        $members = $tontine->members()->where('is_active', true)->get();
        $inactive = $tontine->members()->where('is_active', false)->get();

        $currentCycle = $tontine->cycles()->where('status', 'open')->first();
        $paidIds = $currentCycle
            ? $currentCycle->contributions()->pluck('member_id')->all()
            : [];

        return view('members.index', compact('tontine', 'members', 'inactive', 'currentCycle', 'paidIds'));
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

        $data['position'] = ($tontine->members()->max('position') ?? 0) + 1;

        $tontine->members()->create($data);

        return back()->with('status', __('Manm ajoute.'));
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
            return back()->withErrors(['locked' => __('Tontine a deja kòmanse. Ou pa ka chanje lis manm yo ankò.')]);
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
