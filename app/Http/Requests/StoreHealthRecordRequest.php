<?php

namespace App\Http\Requests;

use App\Models\HealthRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthRecordRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(HealthRecord::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            // Backdating is allowed (e.g. logging last week's vet visit);
            // future dates are not.
            'recorded_at' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }
}
