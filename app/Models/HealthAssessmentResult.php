<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File: app/Models/HealthAssessmentResult.php
 *
 * Purpose:
 *   One ranked line of engine output for an assessment — e.g.
 *   "rank 1: Coccidiosis, match score 38". Every candidate disease that
 *   cleared the threshold gets exactly one row here, and together they are
 *   what the assessment show-endpoint and health timeline display.
 *
 * Snapshot design (important):
 *   This row intentionally COPIES data instead of referencing it live:
 *   disease_name, matched/missing symptom NAMES (JSON), severity, and vet
 *   warning are all frozen at submission time. If an admin later renames a
 *   disease or deactivates a symptom, historical assessments still read
 *   exactly what was recorded that day. The disease_id FK remains only so
 *   admins can trace which knowledge-base entry produced the result.
 */

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

    /**
     * Type conversions:
     * - rank/match_score become integers for arithmetic and comparisons
     * - matched_symptoms / missing_symptoms are stored as JSON strings and
     *   transparently decoded into PHP arrays (and re-encoded on save)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'match_score' => 'integer',
            'matched_symptoms' => 'array',
            'missing_symptoms' => 'array',
        ];
    }

    /**
     * The assessment this result belongs to. Results are always loaded
     * through HealthAssessment::results(), which already orders by rank.
     */
    public function healthAssessment(): BelongsTo
    {
        return $this->belongsTo(HealthAssessment::class);
    }

    /**
     * The knowledge-base disease that produced this suggestion. Kept for
     * traceability only — display uses the snapshot `disease_name` column
     * so history never changes retroactively.
     */
    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
