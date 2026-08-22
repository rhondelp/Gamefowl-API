<?php

namespace App\Http\Requests\Admin;

use App\Models\DiseaseSymptomRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRuleRequest extends FormRequest
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
            'weight' => [
                'required',
                'integer',
                'min:'.DiseaseSymptomRule::WEIGHT_MIN,
                'max:'.DiseaseSymptomRule::WEIGHT_MAX,
            ],
        ];
    }
}
