<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthAssessmentResultResource extends JsonResource
{
    /**
     * Deliberate wording: this system suggests a possible disease based
     * on reported symptoms — it is never presented as a confirmed
     * diagnosis.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            'possible_disease' => [
                'id' => $this->disease_id,
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
