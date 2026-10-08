<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si la base ou la table est indisponible, on NE bloque PAS le site (la page 500 prend le relais ailleurs)
        try {
            $m = Setting::maintenance();
        } catch (\Throwable) {
            return $next($request);
        }

        if (! $m['enabled']) {
            return $next($request);
        }

        // Le propriétaire passe toujours ; la connexion reste ouverte pour qu'il puisse se connecter
        if ($request->user()?->is_owner || $request->is('login') || $request->routeIs('login', 'logout', 'lang.switch')) {
            return $next($request);
        }

        $endsAt = $m['ends_at'] ? Carbon::parse($m['ends_at']) : null;

        if ($request->expectsJson()) {
            return response()->json(['message' => $m['message'] ?: __('Nou ap fè antretyen.')], 503, ['Retry-After' => 300]);
        }

        return response()->view('errors.maintenance', ['message' => $m['message'], 'endsAt' => $endsAt], 503, ['Retry-After' => 300]);
    }
}
