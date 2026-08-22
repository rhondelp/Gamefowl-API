<?php

namespace App\Http\Requests\Admin;

use App\Models\DiseaseSymptomRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/Admin/StoreRuleRequest.php
 *
 * Purpose:
 *   Validates rule creation for POST /api/v1/admin/rules — attaching a
 *   symptom to a disease with an importance weight. These rows ARE the
 *   expert system's knowledge, so validation here is strict.
 */
class StoreRuleRequest extends FormRequest
{
    /**
     * Admin middleware already handled permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * - disease_id / symptom_id: must reference real rows (exists rules).
     * - weight: integer inside the 1–5 range defined by the constants on the
     *   DiseaseSymptomRule model (single source of truth for the scale).
     * - The second rule attached to symptom_id is a COMPOSITE uniqueness
     *   check: the pair (disease_id + symptom_id) must not already exist.
     *   It reports errors under symptom_id, which reads naturally ("this
     *   symptom is already linked to this disease"). The database's unique
     *   index backs this up as a final safety net.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'disease_id' => ['required', 'integer', Rule::exists('diseases', 'id')],
            'symptom_id' => [
                'required',
                'integer',
                Rule::exists('symptoms', 'id'),
                Rule::unique('disease_symptom_rules')->where(
                    fn ($query) => $query->where('disease_id', $this->input('disease_id'))
                ),
            ],
            'weight' => [
                'required',
                'integer',
                'min:'.DiseaseSymptomRule::WEIGHT_MIN,
                'max:'.DiseaseSymptomRule::WEIGHT_MAX,
            ],
        ];
    }
}
