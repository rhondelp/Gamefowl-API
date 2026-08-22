<?php

namespace App\Http\Requests\Auth;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * File: app/Http/Requests/Auth/UpdatePasswordRequest.php
 *
 * Purpose:
 *   Validates the payload for PUT /api/v1/auth/me/password before
 *   AuthController::changePassword runs: proves the caller knows their
 *   CURRENT password, then applies the same strength rules registration
 *   uses to the NEW password.
 *
 * How it fits into the project:
 *   Laravel injects this class into the controller method automatically;
 *   if validation fails, a 422 envelope with per-field errors is returned
 *   and the controller never executes.
 */
class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the request is authorized.
     *
     * The route is already behind auth:sanctum (only a token holder can
     * reach it), and changing one's OWN password needs no further
     * permission decision here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules:
     * - current_password: required AND must actually match the hash stored
     *   for the authenticated user. Done as a manual Hash::check closure on
     *   purpose — Laravel's built-in CurrentPassword rule validates through
     *   the default (session/web) guard, which does not exist under our
     *   stateless Sanctum token auth; checking $this->user()->password is
     *   deterministic here.
     * - new_password: at least 8 characters (the exact rule registration
     *   uses) AND must be repeated identically in a "new_password_confirmation"
     *   field ('confirmed' rule).
     *
     * Deliberately ABSENT: anything else. This endpoint changes exactly one
     * field; role and active status remain admin-only via /admin/users/{id}.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! Hash::check((string) $value, (string) $this->user()?->password)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ],
            'new_password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }
}
