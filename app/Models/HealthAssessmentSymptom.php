<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * File: app/Models/HealthAssessmentSymptom.php
 *
 * Purpose:
 *   Links one health assessment to one symptom the owner selected during
 *   submission, and stores the symptom's NAME as it was at that moment.
 *
 * Why the name snapshot exists:
 *   Admins can rename or deactivate symptoms any time. Without the copied
 *   name, an old assessment would suddenly display different text than what
 *   the owner actually reported — unacceptable for medical-style records.
 *   The snapshot guarantees history reads exactly as submitted. (Same idea
 *   as HealthAssessmentResult's copied fields.)
 *
 * Notes for new developers:
 *   - Rows are created by HealthAssessmentController::store via
 *     attach(id => ['symptom_name' => ...]) — plain attach() without the
 *     name would violate the NOT NULL constraint.
 *   - Extends Pivot because this model backs the belongsToMany relationship
 *     defined on HealthAssessment::symptoms().
 */

class HealthAssessmentSymptom extends Pivot
{
    /**
     * Required override: Laravel's base Pivot assumes the table has no
     * auto-incrementing id. Ours does, and without this flag freshly
     * created pivot rows would report id = null.
     */
    public $incrementing = true;

    protected $table = 'health_assessment_symptoms';

    protected $fillable = [
        'health_assessment_id',
        'symptom_id',
        'symptom_name',
    ];
}
