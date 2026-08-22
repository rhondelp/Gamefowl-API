<?php

namespace App\Http\Requests;

use App\Models\HealthAssessment;
use App\Models\Symptom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * This is the layer responsible for validating that submitted
     * symptoms exist and are active — the DiagnosticEngine (Milestone 5)
     * deliberately treats unknown/inactive IDs as ignorable input.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'symptom_ids' => ['required', 'array', 'min:1', 'max:30'],
            'symptom_ids.*' => [
                'integer',
                Rule::exists('symptoms', 'id')->where('is_active', true),
            ],
            'age_at_assessment' => ['sometimes', 'nullable', 'string', 'max:50'],
            'sex_at_assessment' => ['sometimes', 'nullable', 'string', Rule::in(['male', 'female', 'unknown'])],
            'duration_of_symptoms' => ['sometimes', 'nullable', 'string', Rule::in(HealthAssessment::DURATIONS)],
            'appetite' => ['sometimes', 'nullable', 'string', Rule::in(HealthAssessment::APPETITES)],
            'activity_level' => ['sometimes', 'nullable', 'string', Rule::in(HealthAssessment::ACTIVITY_LEVELS)],
            'additional_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
