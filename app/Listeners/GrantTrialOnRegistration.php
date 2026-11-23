<?php

namespace App\Listeners;

use App\Services\TrialService;
use Illuminate\Auth\Events\Registered;

// Laravel découvre automatiquement ce listener : rien à déclarer dans EventServiceProvider
class GrantTrialOnRegistration
{
    public function handle(Registered $event): void
    {
        app(TrialService::class)->grant($event->user);
    }
}
