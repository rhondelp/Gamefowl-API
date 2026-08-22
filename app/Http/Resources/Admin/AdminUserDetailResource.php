<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

/**
 * File: app/Http/Resources/Admin/AdminUserDetailResource.php
 *
 * Purpose:
 *   Single-user ADMIN detail view. Extends AdminUserResource with two
 *   lightweight aggregate counts (birds owned, assessments submitted).
 *
 * The counts come from withCount() in AdminUserController::show, which runs
 * SQL COUNT queries — the actual bird/assessment records are never loaded,
 * keeping this endpoint fast regardless of account activity.
 */
class AdminUserDetailResource extends AdminUserResource
{
    /**
     * Base user fields + counts.
     *
     * isset() checks make the counts appear only when the controller loaded
     * them; otherwise the keys are null rather than erroring.
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
