<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * File: app/Http/Resources/HealthRecordResource.php
 *
 * Purpose:
 *   The JSON shape of one manual health logbook entry. Used by
 *   HealthRecordController (list/create) and by HealthHistoryController's
 *   status endpoint ("latest_health_record").
 */
class HealthRecordResource extends JsonResource
{
    /**
     * Transform the record into its owner-facing array form.
     *
     * recorded_at is the EVENT date chosen by the owner (possibly backdated),
     * which is different from created_at (when the row was inserted).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'notes' => $this->notes,
            'recorded_at' => $this->recorded_at?->toDateString(),
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
