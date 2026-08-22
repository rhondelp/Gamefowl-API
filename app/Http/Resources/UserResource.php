<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * File: app/Http/Resources/UserResource.php
 *
 * Purpose:
 *   The public shape of a user account in API responses. Used by
 *   AuthController (register/login/me) and extended by the admin resources.
 *
 * Why it exists:
 *   Resources let us decide EXACTLY which fields leave the server. Because
 *   the underlying User model hides password/remember_token at the model
 *   level AND this resource whitelists fields explicitly, a password hash
 *   can never leak into any response by accident. Note there is no `token`
 *   field here — tokens only appear in register/login responses, built
 *   separately in AuthController.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the user into its safe, public array form.
     *
     * Exposed fields: id, name, email, role (so the app knows which UI to
     * show), and creation/update timestamps in ISO-8601 format.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
