<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthAssessmentResource extends JsonResource
{
    public const DISCLAIMER =
        'This assessment is generated from reported symptoms and is not a confirmed '
        .'veterinary diagnosis. Always consult a licensed veterinarian for confirmation '
        .'and treatment, especially for severe or critical findings.';

    /**
     * Full detail view of an immutable health assessment.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gamefowl_id' => $this->gamefowl_id,
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
            'results' => HealthAssessmentResultResource::collection($this->whenLoaded('results')),
            'disclaimer' => self::DISCLAIMER,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
