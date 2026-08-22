<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthAssessmentResult extends Model
{
    protected $fillable = [
        'health_assessment_id',
        'disease_id',
        'disease_name',
        'rank',
        'match_score',
        'matched_symptoms',
        'missing_symptoms',
        'severity_at_assessment',
        'vet_warning_at_assessment',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'match_score' => 'integer',
            'matched_symptoms' => 'array',
            'missing_symptoms' => 'array',
        ];
    }

    public function healthAssessment(): BelongsTo
    {
        return $this->belongsTo(HealthAssessment::class);
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
