<?php

namespace App\Http\Requests;

use App\Models\Gamefowl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/StoreGamefowlRequest.php
 *
 * Purpose:
 *   Validates the payload for POST /api/v1/gamefowls (creating a bird).
 *   All rules mirror the gamefowls table columns so bad data is rejected
 *   before it ever reaches the database.
 *
 * How it fits into the project:
 *   GamefowlController::store receives this class injected; on failure the
 *   caller gets a 422 envelope listing exactly which fields failed and why.
 */
class StoreGamefowlRequest extends FormRequest
{
    /**
     * Creating a bird requires no special permission beyond being logged in
     * — ownership is assigned server-side via the authenticated user.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Field-by-field reasoning:
     * - name: the one required field; every bird needs an identity.
     * - sex: must be one of the exact values defined in Gamefowl::SEXES
     *   (male/female/unknown) — free text here would break filtering later.
     * - date_of_birth / date_acquired: optional, must be valid dates that
     *   are not in the future (you cannot acquire a bird you don't have yet).
     * - weight: optional number between 0 and 20 kg — generous bounds that
     *   still catch typos like 300.
     * - Everything else (breed/color/notes): optional free text with sane
     *   length caps matching column sizes.
     *
     * Deliberately absent: user_id (set from the authenticated user) and
     * is_active (forced to true on creation by the controller).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'breed' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'sex' => ['required', Rule::in(Gamefowl::SEXES)],
            'color' => ['nullable', 'string', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'date_acquired' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
