<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Le lien personnel est dans l'URL : on empêche qu'il parte vers un autre site, qu'il soit mis en cache, ou qu'il soit indexé.
class PortalHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
