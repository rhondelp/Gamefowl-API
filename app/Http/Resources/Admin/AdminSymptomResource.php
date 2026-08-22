<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\SymptomResource;
use Illuminate\Http\Request;

/**
 * File: app/Http/Resources/Admin/AdminSymptomResource.php
 *
 * Purpose:
 *   ADMIN view of a symptom: owner-facing fields plus is_active and
 *   timestamps, since the admin list includes deactivated entries.
 */
class AdminSymptomResource extends SymptomResource
{
    /**
     * Base fields + admin metadata.
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
