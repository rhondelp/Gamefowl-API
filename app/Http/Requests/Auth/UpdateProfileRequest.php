<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/Auth/UpdateProfileRequest.php
 *
 * Purpose:
 *   Validates the payload for PATCH /api/v1/auth/me before
 *   AuthController::updateProfile runs. Lets a user change their own
 *   name/email while keeping email addresses globally unique — except for
 *   their own row, so submitting an unchanged address never fails.
 *
 * How it fits into the project:
 *   Laravel injects this class into the controller method automatically;
 *   if validation fails, a 422 envelope with per-field errors is returned
 *   and the controller never executes.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the request is authorized.
     *
     * The route is already behind auth:sanctum (only a token holder can
     * reach it), and editing one's OWN profile needs no further permission
     * decision here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules:
     * - name: required text, capped at 255 (identical to registration).
     * - email: valid address, lower-cased, unique in users — EXCEPT this
     *   user's own row (Rule::unique(...)->ignore($this->user()->id)), so a
     *   client that re-submits the current address gets success, not a
     *   false "already taken" error.
     *
     * Deliberately ABSENT: any role / active-status field. Those remain
     * admin-only via /admin/users/{id} (Milestone 8) and are additionally
     * allow-listed away in the controller as defense in depth.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc',
                'max:255',
                Rule::unique('users')->ignore($this->user()?->id),
            ],
        ];
    }
}
