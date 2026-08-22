<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * File: app/Http/Resources/HealthAssessmentResultResource.php
 *
 * Purpose:
 *   One ranked line of an assessment's output (used inside
 *   HealthAssessmentResource). Turns a stored HealthAssessmentResult row
 *   into the client-facing explanation of WHY this disease was suggested.
 */
class HealthAssessmentResultResource extends JsonResource
{
    /**
     * Deliberate wording: this system suggests a possible disease based
     * on reported symptoms — it is never presented as a confirmed
     * diagnosis. Hence `possible_disease`, never "diagnosis".
     *
     * matched_symptoms explains what pulled the score up; missing_symptoms
     * powers the transparency feature ("this disease would score higher if
     * the bird also showed X"). Both lists are symptom NAMES from the stored
     * JSON snapshot, so they read correctly forever.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            'possible_disease' => [
                'id' => $this->disease_id,
                // Snapshot name from submission time, not the live record.
                'name' => $this->disease_name,
            ],
            'match_score' => $this->match_score,
            'matched_symptoms' => collect($this->matched_symptoms)->pluck('name')->values(),
            'missing_symptoms' => collect($this->missing_symptoms)->pluck('name')->values(),
            'severity_at_assessment' => $this->severity_at_assessment,
            'vet_warning_at_assessment' => $this->vet_warning_at_assessment,
        ];
    }
}
