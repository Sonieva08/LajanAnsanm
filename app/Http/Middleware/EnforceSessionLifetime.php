<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionLifetime
{
    private const AUTHENTICATED_AT = 'security.authenticated_at';

    public function handle(Request $request, Closure $next): Response
    {
        $isMemberPortal = $request->session()->has('portal_member_id');

        if (! $request->user() && ! $isMemberPortal) {
            return $next($request);
        }

        $authenticatedAt = $request->session()->get(self::AUTHENTICATED_AT);

        if (! is_numeric($authenticatedAt)) {
            $request->session()->put(self::AUTHENTICATED_AT, now()->timestamp);

            return $next($request);
        }

        $maximumAge = (int) config('session.absolute_lifetime', 480) * 60;

        if (now()->timestamp - (int) $authenticatedAt < $maximumAge) {
            return $next($request);
        }

        if ($request->user()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($isMemberPortal) {
            return response()->view('portal.invalid', [], 401);
        }

        return redirect()->route('login');
    }
}
