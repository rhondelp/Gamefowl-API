<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class HealthAssessmentSymptom extends Pivot
{
    public $incrementing = true;

    protected $table = 'health_assessment_symptoms';

    protected $fillable = [
        'health_assessment_id',
        'symptom_id',
        'symptom_name',
    ];
}
