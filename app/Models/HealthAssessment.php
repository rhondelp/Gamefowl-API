<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HealthAssessment extends Model
{
    use HasFactory;

    public const DURATIONS = [
        'less_than_1_day',
        '1_to_3_days',
        '4_to_7_days',
        'more_than_a_week',
    ];

    public const APPETITES = ['normal', 'reduced', 'none'];

    public const ACTIVITY_LEVELS = ['normal', 'reduced', 'lethargic'];

    protected $fillable = [
        'gamefowl_id',
        'age_at_assessment',
        'sex_at_assessment',
        'duration_of_symptoms',
        'appetite',
        'activity_level',
        'additional_notes',
    ];

    public function gamefowl(): BelongsTo
    {
        return $this->belongsTo(Gamefowl::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(HealthAssessmentResult::class)->orderBy('rank');
    }

    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class, 'health_assessment_symptoms')
            ->withPivot('symptom_name')
            ->withTimestamps();
    }
}
