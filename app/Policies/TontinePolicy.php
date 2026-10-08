<?php

namespace App\Policies;

use App\Models\Tontine;
use App\Models\User;

class TontinePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tontine $tontine): bool
    {
        return $user->id === $tontine->admin_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tontine $tontine): bool
    {
        return $user->id === $tontine->admin_id;
    }

    public function delete(User $user, Tontine $tontine): bool
    {
        return $user->id === $tontine->admin_id;
    }
}