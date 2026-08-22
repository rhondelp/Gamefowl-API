<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\RecommendationResource;
use Illuminate\Http\Request;

class AdminRecommendationResource extends RecommendationResource
{
    /**
     * Admin view of a recommendation including the active flag.
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
        ];
    }
}
