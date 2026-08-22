<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Rule weights are intentionally excluded — that is internal
     * knowledge-base data, only exposed through admin endpoints.
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
            'recommended_action' => $this->recommended_action,
            'prevention_info' => $this->prevention_info,
            'vet_warning' => $this->vet_warning,
        ];
    }
}
