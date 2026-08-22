<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GamefowlResource extends JsonResource
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
            'name' => $this->name,
            'breed' => $this->breed,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age,
            'sex' => $this->sex,
            'color' => $this->color,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'date_acquired' => $this->date_acquired?->toDateString(),
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
