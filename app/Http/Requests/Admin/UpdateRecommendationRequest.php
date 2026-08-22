<?php

namespace App\Http\Requests\Admin;

use App\Models\Recommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/Admin/UpdateRecommendationRequest.php
 *
 * Purpose:
 *   Validates partial updates for PUT /api/v1/admin/recommendations/{id}.
 *   {"is_active": true} doubles as the re-activation action.
 */
class UpdateRecommendationRequest extends FormRequest
{
    /**
     * Admin middleware already handled permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Partial-update pattern ('sometimes' on every field). is_active
     * toggling deactivates/reactivates the advice for owners.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string', 'max:5000'],
            'category' => ['sometimes', 'required', 'string', Rule::in(Recommendation::CATEGORIES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
