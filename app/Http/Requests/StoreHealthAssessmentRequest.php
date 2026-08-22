<?php

namespace App\Http\Requests;

use App\Models\HealthAssessment;
use App\Models\Symptom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/StoreHealthAssessmentRequest.php
 *
 * Purpose:
 *   Validates symptom submissions for
 *   POST /api/v1/gamefowls/{gamefowlId}/health-assessments.
 *
 *   This class carries a responsibility that Milestone 5 deliberately left
 *   HERE: checking that submitted symptoms actually exist and are active.
 *   The DiagnosticEngine ignores bad IDs defensively, but the API layer must
 *   reject them loudly — silent acceptance would hide bugs in the mobile app.
 */
class StoreHealthAssessmentRequest extends FormRequest
{
    /**
     * Permission is handled by the controller's policy check (does this user
     * own the bird?); this request only judges payload shape/content.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Field-by-field reasoning:
     * - symptom_ids: required array of 1–30 items. Each item must be an
     *   integer ID that exists in the symptoms table AND has is_active = true
     *   (the ->where() clause adds that condition to the existence query).
     *   The max:30 cap prevents absurdly large payloads from one request.
     * - age_at_assessment / sex_at_assessment: optional client-supplied
     *   snapshots; if omitted the controller copies values from the live bird.
     * - duration_of_symptoms / appetite / activity_level: optional enums,
     *   each constrained to the exact value lists defined on the
     *   HealthAssessment model constants.
     * - additional_notes: free text up to 2000 chars.
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
