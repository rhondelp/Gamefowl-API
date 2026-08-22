<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\DiseaseResource;
use App\Http\Resources\RecommendationResource;
use Illuminate\Http\Request;

/**
 * File: app/Http/Resources/Admin/AdminDiseaseResource.php
 *
 * Purpose:
 *   Full ADMIN view of a disease. Extends the owner-facing DiseaseResource
 *   with everything owners never see:
 *
 *   - `rules`: the complete weighted rule set for this disease (rule_id,
 *     symptom_id, symptom_name, weight) — this is the engine tuning data
 *     admins use to audit and adjust how scoring works.
 *   - `is_active` + timestamps.
 *   - linked recommendations.
 *
 * This asymmetry is deliberate: weights are internal configuration and stay
 * out of every owner-facing payload by construction.
 */
class AdminDiseaseResource extends DiseaseResource
{
    /**
     * Base disease fields plus rules, recommendations, and admin metadata.
     * Assumes ->symptoms has been eager-loaded; each entry's pivot carries
     * the rule id and weight.
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
                // rule_id lets an admin target PUT/DELETE /admin/rules/{id}.
                'rule_id' => $symptom->pivot->id,
                'symptom_id' => $symptom->id,
                'symptom_name' => $symptom->name,
                // Cast to int because pivot values arrive as strings.
                'weight' => (int) $symptom->pivot->weight,
            ]),
            'recommendations' => RecommendationResource::collection($this->whenLoaded('recommendations')),
        ];
    }
}
