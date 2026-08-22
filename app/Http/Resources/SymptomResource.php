<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * File: app/Http/Resources/SymptomResource.php
 *
 * Purpose:
 *   The OWNER-facing shape of a symptom (the assessment checklist data).
 *   Used by SymptomController and as the base class for the admin variant.
 *
 * What's deliberately left out: is_active (owners only ever receive active
 * symptoms) and anything rule-related. Weights live exclusively in the
 * admin resources.
 */
class SymptomResource extends JsonResource
{
    /**
     * Transform the symptom into its owner-facing array form.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'severity' => $this->severity,
        ];
    }
}
