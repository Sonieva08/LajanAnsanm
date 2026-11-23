<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SecureResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.force_https') && app()->environment('production') && ! $request->isSecure()) {
            $appUrl = (string) config('app.url');
            $parts = parse_url($appUrl);

            if (! is_array($parts) || empty($parts['host'])) {
                throw new \InvalidArgumentException('APP_URL must contain a valid host when HTTPS enforcement is enabled.');
            }

            $port = isset($parts['port']) && $parts['port'] !== 443 ? ':'.$parts['port'] : '';
            $uri = $request->getRequestUri();

            return new RedirectResponse('https://'.$parts['host'].$port.(str_starts_with($uri, '/') ? $uri : '/'.$uri), 308);
        }

        $nonce = Str::random(32);
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            // Alpine's standard build evaluates x-data/x-on expressions; inline scripts use the per-response nonce.
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: blob:; font-src 'self' https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-eval' 'nonce-{$nonce}'; connect-src 'self'"
            );

            if ($request->isSecure()) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
            }
        }

        $user = $request->hasSession() ? $request->user() : null;
        $isMemberPortal = $request->hasSession() && $request->session()->has('portal_member_id');

        if ($user || $isMemberPortal || $request->routeIs('admin.*', 'login', 'register', 'password.*')) {
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        if (in_array($response->getStatusCode(), [401, 403, 429], true)) {
            Log::notice('Request denied or rate limited', [
                'status' => $response->getStatusCode(),
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'user_id' => $user?->id,
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
