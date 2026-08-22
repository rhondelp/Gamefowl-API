<?php

namespace App\Http\Requests;

use App\Models\Gamefowl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGamefowlRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'breed' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'sex' => ['sometimes', 'required', Rule::in(Gamefowl::SEXES)],
            'color' => ['sometimes', 'nullable', 'string', 'max:100'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:20'],
            'date_acquired' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
