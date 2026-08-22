<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * File: app/Http/Requests/Auth/LoginRequest.php
 *
 * Purpose:
 *   Validates the payload for POST /api/v1/auth/login before
 *   AuthController::login attempts authentication.
 *
 * How it fits into the project:
 *   Only FORMAT is checked here (fields present, email-shaped). Whether the
 *   credentials are actually correct is decided in the controller — that's
 *   where identical "Invalid credentials." errors are produced for unknown
 *   emails and wrong passwords alike, preventing account enumeration.
 */
class LoginRequest extends FormRequest
{
    /**
     * Login requires no special permissions — anyone may attempt it
     * (route-level throttling handles abuse).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Both fields are required; email just needs to LOOK like an email (no
     * uniqueness check here — a wrong-but-well-formed address must fail in
     * the controller with the same message as a wrong password).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
