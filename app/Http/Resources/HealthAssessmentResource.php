<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * File: app/Http/Resources/HealthAssessmentResource.php
 *
 * Purpose:
 *   Full detail shape of one health assessment — used by both the create
 *   response (POST .../health-assessments) and the detail endpoint
 *   (GET /health-assessments/{id}).
 *
 * Cautious-wording contract:
 *   Results are labeled "possible_disease", never "diagnosis", and every
 *   response carries a static DISCLAIMER reminding users this tool does not
 *   replace veterinary care. That wording decision was made deliberately and
 *   is enforced here so it cannot drift between endpoints.
 */
class HealthAssessmentResource extends JsonResource
{
    /**
     * Shown on every assessment response; wording approved for the capstone.
     */
    public const DISCLAIMER =
        'This assessment is generated from reported symptoms and is not a confirmed '
        .'veterinary diagnosis. Always consult a licensed veterinarian for confirmation '
        .'and treatment, especially for severe or critical findings.';

    /**
     * Transform the assessment into its full detail form.
     *
     * Everything included here comes from SNAPSHOT columns/relations, so the
     * output is frozen at submission time: submitted_symptoms uses the pivot's
     * stored symptom_name, results use stored disease names/scores/severity.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gamefowl_id' => $this->gamefowl_id,
            // Context snapshots captured at submission time.
            'age_at_assessment' => $this->age_at_assessment,
            'sex_at_assessment' => $this->sex_at_assessment,
            'duration_of_symptoms' => $this->duration_of_symptoms,
            'appetite' => $this->appetite,
            'activity_level' => $this->activity_level,
            'additional_notes' => $this->additional_notes,
            'submitted_symptoms' => $this->symptoms->map(fn ($symptom) => [
                'id' => $symptom->id,
                // Snapshot taken at submission time; immune to later renames.
                'name' => $symptom->pivot->symptom_name,
            ]),
            // Ranked engine output (HealthAssessmentResult rows), best first.
            'results' => HealthAssessmentResultResource::collection($this->whenLoaded('results')),
            'disclaimer' => self::DISCLAIMER,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
