<?php

namespace App\Http\Requests\Admin;

use App\Models\Disease;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/Admin/StoreDiseaseRequest.php
 *
 * Purpose:
 *   Validates disease creation for POST /api/v1/admin/diseases.
 */
class StoreDiseaseRequest extends FormRequest
{
    /**
     * Admin middleware already handled permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * - name: required and unique — diseases are the identity of assessment
     *   results, so duplicates would be confusing.
     * - description / recommended_action: REQUIRED because every disease in
     *   the knowledge base must at minimum explain what it is and what to do.
     * - severity: one of Disease::SEVERITIES, which includes 'critical'.
     * - general_info / prevention_info: optional educational content.
     * - vet_warning: optional; shown to owners only when the disease's
     *   severity reaches severe/critical (gated by DiagnosticEngine).
     *
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
