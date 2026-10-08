<?php

namespace App\Http\Middleware;

use App\Models\Tontine;
use App\Support\Entitlements;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique les limites et fonctions du plan côté SERVEUR (cacher un bouton ne suffit jamais).
 * Le propriétaire n'est jamais limité.
 */
class EnforceSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = $request->route();
        $name = $route?->getName();

        if (! $user || $user->is_owner || ! $name || ! str_starts_with($name, 'tontines.')) {
            return $next($request);
        }

        $ent = Entitlements::for($user);

        if ($name === 'tontines.store' && ! $ent->canCreateTontine()) {
            return $this->limit($request, 'tontines', $ent->activeTontines(), $ent->maxTontines());
        }

        $tontine = $route->parameter('tontine');
        if (is_scalar($tontine)) {
            $tontine = Tontine::find($tontine);
        }

        // Si la tontine n'est pas à lui, c'est la Policy du contrôleur qui répond (403)
        if (! $tontine instanceof Tontine || $tontine->admin_id !== $user->id) {
            return $next($request);
        }

        $isWrite = ! in_array($request->method(), ['GET', 'HEAD'], true);

        if ($isWrite && $name !== 'tontines.destroy' && ! $ent->isWritable($tontine)) {
            return $this->readonly($request);
        }

        if (in_array($name, ['tontines.members.store', 'tontines.members.reactivate'], true) && ! $ent->canAddMember($tontine)) {
            return $this->limit($request, 'members', $tontine->members()->where('is_active', true)->count(), $ent->maxMembers());
        }

        if ($name === 'tontines.cycles.restart' && ! $ent->has('restart_round')) {
            return $this->limit($request, 'restart_round');
        }

        if (str_starts_with($name, 'tontines.reports.') && $request->query('format') === 'csv' && ! $ent->has('csv_export')) {
            return $this->limit($request, 'csv_export');
        }

        return $next($request);
    }

    private function limit(Request $request, string $need, ?int $used = null, ?int $max = null): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('Ou rive nan limit plan ou an.'), 'need' => $need], 403);
        }

        return redirect()->route('plans')->with('limit', ['need' => $need, 'used' => $used, 'max' => $max]);
    }

    private function readonly(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('Tontin sa a an lekti sèlman.')], 403);
        }

        return back()->with('subscription_notice', __('Tontin sa a an lekti sèlman paske plan ou an pa kouvri li ankò. Renouvle oswa chwazi yon plan pou modifye li.'));
    }
}
