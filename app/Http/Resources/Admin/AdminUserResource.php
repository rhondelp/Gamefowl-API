<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * File: app/Http/Resources/Admin/AdminUserResource.php
 *
 * Purpose:
 *   ADMIN list-view of a user account. Extends the safe owner-facing
 *   UserResource with the active flag and soft-delete timestamp, because
 *   admins manage deactivated accounts too.
 *
 * Password hashes and tokens remain hidden — they're excluded by the User
 * model AND absent from the parent resource's whitelist.
 */
class AdminUserResource extends UserResource
{
    /**
     * Base user fields + admin metadata.
     * is_active is true whenever deleted_at is null (soft deletes are how
     * accounts get deactivated).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'is_active' => $this->deleted_at === null,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
