<?php

namespace App\Policies;

use App\Models\Gamefowl;
use App\Models\User;

/**
 * File: app/Policies/GamefowlPolicy.php
 *
 * Purpose:
 *   THE single source of truth for gamefowl ownership. Every "may this user
 *   touch this bird?" decision in the application flows through here —
 *   including other resources: HealthAssessmentPolicy injects this class and
 *   delegates its checks to it.
 *
 * How policies work (for new developers):
 *   Controllers call `$this->authorize('view', $gamefowl)`. Laravel finds
 *   this class automatically by naming convention
 *   (App\Models\Gamefowl -> App\Policies\GamefowlPolicy), calls the method
 *   matching the ability name, and denies the request if it returns false.
 *   A denial surfaces as a uniform 404 for API routes (anti-enumeration:
 *   callers can't tell "missing" from "not yours").
 */
class GamefowlPolicy
{
    /**
     * May the user list birds at all? Any authenticated user passes; the
     * actual per-owner filtering happens in the controller via
     * $user->gamefowls(), which only ever returns their own rows.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * May the user see THIS bird? The core ownership comparison every other
     * check reuses: the bird's user_id must match the caller's id.
     */
    public function view(User $user, Gamefowl $gamefowl): bool
    {
        return $user->id === $gamefowl->user_id;
    }

    /**
     * May the user create birds? Yes for any authenticated user — new birds
     * automatically belong to whoever is creating them.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * May the user edit THIS bird? Owner-only, same comparison as view().
     */
    public function update(User $user, Gamefowl $gamefowl): bool
    {
        return $user->id === $gamefowl->user_id;
    }

    /**
     * May the user soft-delete THIS bird? Owner-only, same comparison as
     * view(). Soft deletes keep assessment history alive.
     */
    public function delete(User $user, Gamefowl $gamefowl): bool
    {
        return $user->id === $gamefowl->user_id;
    }
}
