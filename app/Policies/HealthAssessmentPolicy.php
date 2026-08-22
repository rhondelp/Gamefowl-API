<?php

namespace App\Policies;

use App\Models\HealthAssessment;
use App\Models\Gamefowl;
use App\Models\User;

class HealthAssessmentPolicy
{
    public function __construct(private GamefowlPolicy $gamefowlPolicy)
    {
    }

    /**
     * Ownership is delegated to GamefowlPolicy so the check lives in
     * exactly one place.
     */
    public function create(User $user, Gamefowl $gamefowl): bool
    {
        return $this->gamefowlPolicy->view($user, $gamefowl);
    }

    public function view(User $user, HealthAssessment $healthAssessment): bool
    {
        return $this->gamefowlPolicy->view($user, $healthAssessment->gamefowl);
    }
}
