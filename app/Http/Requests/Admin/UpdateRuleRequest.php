<?php

namespace App\Http\Requests\Admin;

use App\Models\DiseaseSymptomRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * File: app/Http/Requests/Admin/UpdateRuleRequest.php
 *
 * Purpose:
 *   Validates weight changes for PUT /api/v1/admin/rules/{id}.
 *   Weight is the ONLY editable property of a rule — moving a connection to
 *   a different disease/symptom pair means deleting and recreating it, which
 *   keeps every stored row unambiguous.
 */
class UpdateRuleRequest extends FormRequest
{
    /**
     * Admin middleware already handled permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Same 1–5 bounds as creation, sourced from the model constants so the
     * scale can never drift between create and update paths.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'weight' => [
                'required',
                'integer',
                'min:'.DiseaseSymptomRule::WEIGHT_MIN,
                'max:'.DiseaseSymptomRule::WEIGHT_MAX,
            ],
        ];
    }
}
