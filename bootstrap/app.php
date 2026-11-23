<?php

use App\Http\Middleware\BlockDisabledUsers;
use App\Http\Middleware\CheckMaintenance;
use App\Http\Middleware\EnforceSubscription;
use App\Http\Middleware\EnsureMemberSession;
use App\Http\Middleware\EnforceSessionLifetime;
use App\Http\Middleware\SecureResponse;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackLastSeen;
use App\Http\Middleware\TrustConfiguredProxies;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->replace(TrustProxies::class, TrustConfiguredProxies::class);
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PREFIX,
        );
        $middleware->append(SecureResponse::class);
        $middleware->web(append: [
            SetLocale::class,
            BlockDisabledUsers::class,
            CheckMaintenance::class,
            EnforceSubscription::class,
            EnforceSessionLifetime::class,
            TrackLastSeen::class,
        ]);
        $middleware->alias(['member.portal' => EnsureMemberSession::class]);
        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            CheckMaintenance::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
