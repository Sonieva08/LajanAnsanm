<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;

class TrustConfiguredProxies extends TrustProxies
{
    protected function proxies(): array|string|null
    {
        return config('app.trusted_proxies');
    }
}
