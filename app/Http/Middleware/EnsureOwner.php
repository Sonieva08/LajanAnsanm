<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

// Tout le monde sauf le propriétaire reçoit une 404 : l'existence de /admin n'est pas révélée
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_owner) {
            Log::notice('Owner-only route access denied', [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
            ]);

            abort(404);
        }

        return $next($request);
    }
}
