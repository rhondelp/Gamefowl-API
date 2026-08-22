<?php

namespace App\Http\Requests\Admin;

use App\Models\Disease;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiseaseRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique(Disease::class)],
            'description' => ['required', 'string', 'max:2000'],
            'severity' => ['required', 'string', Rule::in(Disease::SEVERITIES)],
            'general_info' => ['nullable', 'string', 'max:5000'],
            'recommended_action' => ['required', 'string', 'max:2000'],
            'prevention_info' => ['nullable', 'string', 'max:2000'],
            'vet_warning' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
