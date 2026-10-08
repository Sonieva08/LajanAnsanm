<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeOwner extends Command
{
    protected $signature = 'lajan:make-owner {email : E-mail du compte} {--revoke : Retirer le rôle propriétaire}';

    protected $description = 'Donne ou retire le rôle propriétaire (accès à /admin)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Aucun compte avec cet e-mail.');

            return self::FAILURE;
        }

        $user->forceFill(['is_owner' => ! $this->option('revoke')])->save();

        $this->info($user->is_owner
            ? "{$user->email} est maintenant propriétaire."
            : "{$user->email} n'est plus propriétaire.");

        return self::SUCCESS;
    }
}
