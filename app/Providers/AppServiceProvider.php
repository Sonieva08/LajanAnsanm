<?php

namespace App\Providers;

use App\Support\ActivityLogger;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ActivityLogger::register();

        // Règles de mot de passe : robustes dans tous les environnements.
        // En production : vérifie aussi les fuites de données (Have I Been Pwned).
        Password::defaults(function () {
            $base = Password::min(10);

            if (app()->isProduction()) {
                return $base->mixedCase()->numbers()->uncompromised()->rules([new \App\Rules\NotCommonPassword()]);
            }

            if (! app()->environment('testing')) {
                return $base->mixedCase()->numbers()->symbols()->rules([new \App\Rules\NotCommonPassword()]);
            }

            return $base;
        });

        ResetPassword::createUrlUsing(function (CanResetPassword $user, string $token): string {
            $path = URL::route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false);

            return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
        });

        // Console propriétaire et pages d'abonnement : fichiers de routes séparés
        Route::middleware('web')->group(base_path('routes/admin.php'));
        Route::middleware('web')->group(base_path('routes/help.php'));
        Route::middleware(['web', 'auth'])->group(base_path('routes/subscription.php'));

        // Lien personnel du portail membre : limité par jeton ET par IP
        // (force brute sur /m/{token} ou harcèlement d'un membre précis)
        RateLimiter::for('portal.token', function (Request $request) {
            $token = (string) $request->route('token');

            return [
                Limit::perMinute(10)->by('portal-token:'.$token),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });
    }
}
