<?php

namespace App\Http\Requests;

use App\Models\HealthRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/StoreHealthRecordRequest.php
 *
 * Purpose:
 *   Validates manual logbook entries for
 *   POST /api/v1/gamefowls/{gamefowlId}/health-records — the human-entered
 *   counterpart to engine-generated assessments.
 */
class StoreHealthRecordRequest extends FormRequest
{
    /**
     * Ownership is enforced by the controller (bird lookup + policy); this
     * request only validates content.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Field-by-field reasoning:
     * - type: required, must be one of HealthRecord::TYPES (vet_visit,
     *   weight_check, general_note, vaccination). A small fixed enum keeps
     *   the timeline filterable without over-engineering.
     * - title: required short label shown in history lists.
     * - notes: optional long-form detail.
     * - recorded_at: optional date. BACKDATING is allowed (logging last
     *   week's vet visit is the whole point) but future dates are rejected —
     *   you can't record something that hasn't happened. If omitted, the
     *   controller defaults to today.
     * - weight: optional number; only meaningful for weight_check entries
     *   but kept nullable on the shared table rather than splitting into
     *   per-type sub-tables at this scope.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(HealthRecord::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            // Backdating allowed (e.g. logging last week's vet visit);
            // future dates are not.
            'recorded_at' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }
}
