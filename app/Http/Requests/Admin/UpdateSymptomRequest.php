<?php

namespace App\Http\Requests\Admin;

use App\Models\Symptom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/Admin/UpdateSymptomRequest.php
 *
 * Purpose:
 *   Validates partial updates for PUT /api/v1/admin/symptoms/{id}. Also the
 *   re-activation path: sending {"is_active": true} restores a deactivated
 *   symptom to the owner-facing checklist.
 */
class UpdateSymptomRequest extends FormRequest
{
    /**
     * Admin middleware already handled permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 'sometimes' enables partial updates. The unique rule on name uses
     * ->ignore($this->route('symptom')) so a symptom keeping its own current
     * name doesn't collide with itself; renaming to ANOTHER symptom's name
     * still fails.
     * is_active is admin-only here: deactivating hides the symptom from
     * owners and drops its rules out of engine scoring without deleting it.
     *
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
