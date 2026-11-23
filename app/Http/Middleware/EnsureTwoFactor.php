<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Si le propriétaire a activé la double vérification, la console n'ouvre qu'après le code du moment
class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->two_factor_confirmed_at && ! $request->session()->get('admin_2fa_ok')) {
            if ($request->isMethod('GET')) {
                $request->session()->put('admin_2fa_intended', $request->fullUrl());
            }

            return redirect()->route('admin.security.verify');
        }

        return $next($request);
    }
}
