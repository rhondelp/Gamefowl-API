<?php

namespace App\Http\Requests\Admin;

use App\Models\Recommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/Admin/StoreRecommendationRequest.php
 *
 * Purpose:
 *   Validates recommendation creation for POST /api/v1/admin/recommendations.
 */
class StoreRecommendationRequest extends FormRequest
{
    /**
     * Admin middleware already handled permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * - title: short heading shown in the app's advice lists.
     * - content: the actual guidance text (the meat of the entry).
     * - category: constrained to Recommendation::CATEGORIES rather than
     *   free-form, so the mobile app can group advice predictably; new
     *   categories are added by extending the model constant.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', Rule::in(Recommendation::CATEGORIES)],
        ];
    }
}
