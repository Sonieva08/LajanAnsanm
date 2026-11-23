<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accès à l'espace membre : le membre arrive par son lien personnel (/m/{jeton}), puis la session le reconnaît.
 * Si le créateur renouvelle le lien, désactive le membre ou ferme le portail, la session cesse de fonctionner.
 */
class EnsureMemberSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('portal_member_id');
        $hash = $request->session()->get('portal_token_hash');

        $member = $id ? Member::with('tontine')->find($id) : null;

        if (! $member
            || ! $member->is_active
            || ! $member->tontine->member_portal_enabled
            || $member->tontine->status === 'completed'
            || ! ($member->tontine->is_legacy || \App\Support\Entitlements::for($member->tontine->admin)->has('member_portal'))
            || ! $member->access_token_hash
            || ! hash_equals($member->access_token_hash, (string) $hash)) {
            $request->session()->forget(['portal_member_id', 'portal_token_hash']);

            return response()->view('portal.invalid', [], 403);
        }

        $request->attributes->set('portal_member', $member);

        return $next($request);
    }
}
