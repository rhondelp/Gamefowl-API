<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\SymptomResource;
use Illuminate\Http\Request;

class AdminSymptomResource extends SymptomResource
{
    /**
     * Admin view of a symptom including the active flag.
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
