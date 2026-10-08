<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Tout le monde sauf le propriétaire reçoit une 404 : l'existence de /admin n'est pas révélée
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_owner, 404);

        return $next($request);
    }
}
