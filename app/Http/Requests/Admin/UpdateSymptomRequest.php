<?php

namespace App\Http\Requests\Admin;

use App\Models\Symptom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSymptomRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique(Symptom::class)->ignore($this->route('symptom'))],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'severity' => ['sometimes', 'required', 'string', Rule::in(Symptom::SEVERITIES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
