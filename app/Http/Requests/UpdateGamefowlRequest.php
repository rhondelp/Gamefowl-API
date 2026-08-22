<?php

namespace App\Http\Requests;

use App\Models\Gamefowl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/UpdateGamefowlRequest.php
 *
 * Purpose:
 *   Validates partial updates for PUT/PATCH /api/v1/gamefowls/{id}.
 *   The owner may send ANY SUBSET of fields; only the ones present are
 *   validated and applied. This is what makes PATCH-style updates work.
 *
 * How it fits into the project:
 *   Used by GamefowlController::update. Setting "is_active": false through
 *   this request is how an owner retires a bird without deleting it.
 */
class UpdateGamefowlRequest extends FormRequest
{
    /**
     * Updating a bird requires only ownership, which the controller's policy
     * check already enforced before this class runs.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The 'sometimes' prefix on every rule means "only validate this field
     * if it was actually sent" — that's what enables partial updates.
     * ('sometimes','nullable') together also allow explicitly sending null
     * to clear a value.
     *
     * Same field constraints as StoreGamefowlRequest, plus:
     * - is_active: boolean toggle for retiring/reactivating a bird.
     * - No user_id rule: ownership of a bird never changes hands.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'breed' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'sex' => ['sometimes', 'required', Rule::in(Gamefowl::SEXES)],
            'color' => ['sometimes', 'nullable', 'string', 'max:100'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:20'],
            'date_acquired' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
