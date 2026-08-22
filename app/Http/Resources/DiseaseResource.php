<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * File: app/Http/Resources/DiseaseResource.php
 *
 * Purpose:
 *   The OWNER-facing shape of a disease — educational content shown when
 *   browsing conditions or viewing an assessment. Used by
 *   DiseaseController; extended by Admin\AdminDiseaseResource.
 *
 * Privacy rule enforced here: rule WEIGHTS are internal engine tuning and
 * are NEVER included in this resource. Admins read them through
 * /admin/diseases instead. is_active is likewise omitted — owners only ever
 * see active diseases anyway, so exposing the flag would leak nothing but
 * noise.
 */
class DiseaseResource extends JsonResource
{
    /**
     * Transform the disease into its owner-facing array form.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'severity' => $this->severity,
            'general_info' => $this->general_info,
            // Action guidance the owner should take for this condition.
            'recommended_action' => $this->recommended_action,
            'prevention_info' => $this->prevention_info,
            // Warning text for severe/critical conditions; surfaced by the
            // engine only above a severity threshold.
            'vet_warning' => $this->vet_warning,
        ];
    }
}
