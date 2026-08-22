<?php

namespace App\Http\Requests\Admin;

use App\Models\Symptom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/Admin/StoreSymptomRequest.php
 *
 * Purpose:
 *   Validates symptom creation for POST /api/v1/admin/symptoms.
 *
 * How it fits into the project:
 *   Runs behind auth:sanctum + admin middleware. The unique rule prevents
 *   duplicate names, which would confuse owners picking symptoms from the
 *   checklist during assessment submission.
 */
class StoreSymptomRequest extends FormRequest
{
    /**
     * Route middleware already guaranteed an admin; nothing further to check.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * - name: required and UNIQUE across the symptoms table (duplicate names
     *   would make the owner-facing checklist ambiguous).
     * - category: free-form grouping label (e.g. respiratory) used by the
     *   ?grouped=1 view of the public symptoms endpoint.
     * - severity: must be one of Symptom::SEVERITIES (mild/moderate/severe —
     *   no 'critical' at the individual-symptom level).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Symptom::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:100'],
            'severity' => ['required', 'string', Rule::in(Symptom::SEVERITIES)],
        ];
    }
}
