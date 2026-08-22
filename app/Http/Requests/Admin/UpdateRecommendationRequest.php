<?php

namespace App\Http\Requests\Admin;

use App\Models\Recommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
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
