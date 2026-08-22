<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * File: app/Http/Resources/GamefowlResource.php
 *
 * Purpose:
 *   The JSON shape of a gamefowl profile for owner-facing endpoints
 *   (list/show/create/update in GamefowlController).
 *
 * Notable decisions:
 * - `weight` is cast to a float so clients receive 3.2 rather than "3.20".
 * - `age` is COMPUTED on the fly from date_of_birth (see Gamefowl::age()) —
 *   storing an age column would go stale; computing keeps it always current.
 * - user_id is intentionally omitted: owners already know their own ID and
 *   other users can never see someone else's birds anyway.
 */
class GamefowlResource extends JsonResource
{
    /**
     * Transform the bird into its owner-facing array form.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'breed' => $this->breed,
            // ->toDateString() normalizes Carbon dates to YYYY-MM-DD strings.
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
