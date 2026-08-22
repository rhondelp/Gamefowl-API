<?php

namespace App\Policies;

use App\Models\Gamefowl;
use App\Models\User;

class GamefowlPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Gamefowl $gamefowl): bool
    {
        return $user->id === $gamefowl->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Gamefowl $gamefowl): bool
    {
        return $user->id === $gamefowl->user_id;
    }

    public function delete(User $user, Gamefowl $gamefowl): bool
    {
        return $user->id === $gamefowl->user_id;
    }
}
