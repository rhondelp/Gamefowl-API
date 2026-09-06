<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * File: app/Http/Requests/Auth/ForgotPasswordRequest.php
 *
 * Purpose:
 *   Validates the payload for POST /api/v1/auth/forgot-password.
 *   Only validates email FORMAT — NOT whether the email exists in the database.
 *   This prevents account enumeration (the underlying Password broker handles
 *   the "user not found" case silently and always returns the same response).
 */
class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the request is authorized.
     *
     * This is a public, unauthenticated endpoint.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules:
     * - email: required, valid email format.
     *   No uniqueness check here — returning the same success response
     *   regardless of whether the account exists is standard security practice.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }
}