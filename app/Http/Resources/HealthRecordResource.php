<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'notes' => $this->notes,
            'recorded_at' => $this->recorded_at?->toDateString(),
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
