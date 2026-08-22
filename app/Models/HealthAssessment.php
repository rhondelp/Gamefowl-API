<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * File: app/Models/HealthAssessment.php
 *
 * Purpose:
 *   One diagnostic event: an owner submitted a set of symptoms for one of
 *   their birds, the DiagnosticEngine scored them, and everything about
 *   that moment was saved here — including SNAPSHOT copies (symptom names,
 *   disease names, scores) so history stays accurate even if the knowledge
 *   base changes later.
 *
 * How it fits into the project:
 *   - Created by HealthAssessmentController::store inside a DB transaction
 *     (assessment + symptom links + results all succeed or all roll back).
 *   - Read by HealthAssessmentController::show and summarized in
 *     HealthHistoryController's timeline/status endpoints.
 *
 * Notes for new developers:
 *   - Assessments are APPEND-ONLY. There is deliberately no update or
 *     delete endpoint anywhere; these are medical-style historical records.
 */

class HealthAssessment extends Model
{
    use HasFactory;

    /**
     * Optional context the owner supplies with a submission: how long the
     * bird has shown symptoms. Validated by StoreHealthAssessmentRequest.
     */
    public const DURATIONS = [
        'less_than_1_day',
        '1_to_3_days',
        '4_to_7_days',
        'more_than_a_week',
    ];

    /**
     * Optional context: the bird's appetite when observed.
     */
    public const APPETITES = ['normal', 'reduced', 'none'];

    /**
     * Optional context: how active the bird seemed when observed.
     */
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

    /**
     * The bird this assessment was recorded for. Ownership of this bird is
     * what decides who may view the assessment (HealthAssessmentPolicy).
     */
    public function gamefowl(): BelongsTo
    {
        return $this->belongsTo(Gamefowl::class);
    }

    /**
     * Ranked engine output for this assessment (best match first).
     *
     * The orderBy('rank') lives HERE so every consumer — show endpoint,
     * timeline summaries, admin dashboard "top result" lookups — gets
     * correctly ordered results for free without re-sorting.
     */
    public function results(): HasMany
    {
        return $this->hasMany(HealthAssessmentResult::class)->orderBy('rank');
    }

    /**
     * The symptoms the owner selected at submission time.
     *
     * The pivot stores `symptom_name` as a snapshot copy: if an admin later
     * renames or deactivates the Symptom record, this assessment still shows
     * exactly what was reported that day.
     */
    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class, 'health_assessment_symptoms')
            ->withPivot('symptom_name')
            ->withTimestamps();
    }
}
