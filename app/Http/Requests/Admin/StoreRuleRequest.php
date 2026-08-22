<?php

namespace App\Http\Requests\Admin;

use App\Models\DiseaseSymptomRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRuleRequest extends FormRequest
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
