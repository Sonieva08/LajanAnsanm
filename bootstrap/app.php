<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
  ->withMiddleware(function (Middleware $middleware): void {
  $middleware->web(append: [
      \App\Http\Middleware\SetLocale::class,
      \App\Http\Middleware\BlockDisabledUsers::class,
      \App\Http\Middleware\CheckMaintenance::class,
      \App\Http\Middleware\EnforceSubscription::class,
      \App\Http\Middleware\TrackLastSeen::class,
  ]);
    $middleware->prependToPriorityList(
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        \App\Http\Middleware\CheckMaintenance::class,
    );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
