<?php

namespace App\Http\Requests\Admin;

use App\Models\Disease;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiseaseRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique(Disease::class)->ignore($this->route('disease'))],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'severity' => ['sometimes', 'required', 'string', Rule::in(Disease::SEVERITIES)],
            'general_info' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'recommended_action' => ['sometimes', 'required', 'string', 'max:2000'],
            'prevention_info' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'vet_warning' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
