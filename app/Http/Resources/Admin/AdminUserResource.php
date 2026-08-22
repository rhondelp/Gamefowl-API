<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AdminUserResource extends UserResource
{
    /**
     * Admin view of a user: adds the active flag and soft-delete stamp.
     * Password hashes and tokens stay hidden (inherited from UserResource).
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
