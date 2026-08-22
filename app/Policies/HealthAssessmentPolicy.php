<?php

namespace App\Policies;

use App\Models\HealthAssessment;
use App\Models\Gamefowl;
use App\Models\User;

/**
 * File: app/Policies/HealthAssessmentPolicy.php
 *
 * Purpose:
 *   Decides who may create or view health assessments. Assessments have no
 *   permissions of their own — their access is entirely derived from WHO OWNS
 *   THE BIRD the assessment was made for.
 *
 * Design choice (important):
 *   Instead of re-writing the user_id comparison, this policy INJECTS
 *   GamefowlPolicy and delegates every check to it. One ownership rule,
 *   enforced in one place — if ownership logic ever changes, both resources
 *   update together automatically. (Laravel injects the constructor argument
 *   via its service container.)
 */
class HealthAssessmentPolicy
{
    public function __construct(private GamefowlPolicy $gamefowlPolicy)
    {
    }

    /**
     * May the user submit an assessment for this bird?
     * Same question as "do they own the bird?" -> delegate to GamefowlPolicy.
     * Called from HealthAssessmentController::store before any writes happen.
     */
    public function create(User $user, Gamefowl $gamefowl): bool
    {
        return $this->gamefowlPolicy->view($user, $gamefowl);
    }

    /**
     * May the user view this assessment?
     *
     * Loads the parent bird through the relation and asks the same ownership
     * question. Called from HealthAssessmentController::show; a denial ends
     * up as a generic 404 (anti-enumeration).
     */
    public function view(User $user, HealthAssessment $healthAssessment): bool
    {
        return $this->gamefowlPolicy->view($user, $healthAssessment->gamefowl);
    }
}
