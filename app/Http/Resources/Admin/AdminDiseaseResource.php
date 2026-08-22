<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\DiseaseResource;
use App\Http\Resources\RecommendationResource;
use Illuminate\Http\Request;

class AdminDiseaseResource extends DiseaseResource
{
    /**
     * Full admin view of a disease including rule weights and
     * linked recommendations.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'rules' => $this->symptoms->map(fn ($symptom) => [
                'rule_id' => $symptom->pivot->id,
                'symptom_id' => $symptom->id,
                'symptom_name' => $symptom->name,
                'weight' => (int) $symptom->pivot->weight,
            ]),
            'recommendations' => RecommendationResource::collection($this->whenLoaded('recommendations')),
        ];
    }
}
