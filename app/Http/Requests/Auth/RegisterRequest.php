<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * File: app/Http/Requests/Auth/RegisterRequest.php
 *
 * Purpose:
 *   Validates the payload for POST /api/v1/auth/register before
 *   AuthController::register runs. Centralizing rules here means the
 *   controller stays clean and every registration path is validated the
 *   same way.
 *
 * How it fits into the project:
 *   Laravel injects this class into the controller method automatically;
 *   if validation fails, a 422 envelope with per-field errors is returned
 *   and the controller never executes.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the request is authorized.
     *
     * Registration is open to the public; the role is assigned server-side
     * (always `owner`), never taken from the payload — so there is nothing
     * permission-related to decide here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules:
     * - name: required text, capped at 255 (matches the DB column).
     * - email: valid address, lower-cased, and unique in the users table —
     *   Rule::unique(User::class) checks the users table via the model,
     *   preventing duplicate accounts.
     * - password: at least 8 characters AND must be repeated identically in
     *   a "password_confirmation" field ('confirmed' rule).
     *
     * Deliberately ABSENT: any `role` field. Registration always creates an
     * owner; accepting role here would let anyone self-promote to admin.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }
}
