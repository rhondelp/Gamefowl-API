<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserDetailResource extends AdminUserResource
{
    /**
     * Single-user admin view: adds lightweight aggregate counts, never
     * nested dumps of the user's birds or assessments.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'gamefowl_count' => isset($this->gamefowls_count) ? (int) $this->gamefowls_count : null,
            'health_assessment_count' => isset($this->health_assessments_count) ? (int) $this->health_assessments_count : null,
        ];
    }
}
