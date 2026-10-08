<?php

namespace App\Providers;

use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ActivityLogger::register();

        // Console propriétaire et pages d'abonnement : fichiers de routes séparés
        Route::middleware('web')->group(base_path('routes/admin.php'));
        Route::middleware(['web', 'auth'])->group(base_path('routes/subscription.php'));
    }
}
