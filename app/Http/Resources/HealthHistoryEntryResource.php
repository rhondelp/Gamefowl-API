<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Normalizes the two timeline source types (system-generated health
 * assessments and manual health records) into one consistent entry shape,
 * tagged with a `type` discriminator so clients can render them
 * differently. Assessment entries stay summarized — full diagnostic
 * detail remains available via GET /api/v1/health-assessments/{id}.
 *
 * The underlying resource is a pre-normalized array built by
 * HealthHistoryController.
 */
class HealthHistoryEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $entry */
        $entry = $this->resource;

        /** @var Carbon $occurredAt */
        $occurredAt = $entry['occurred_at'];

        if ($entry['type'] === 'assessment') {
            return [
                'type' => 'assessment',
                'assessment_id' => $entry['id'],
                'occurred_at' => $occurredAt->toIso8601String(),
                'top_possible_disease' => $entry['top_possible_disease'],
                'match_score' => $entry['match_score'],
                'severity_at_assessment' => $entry['severity_at_assessment'],
            ];
        }

        return [
            'type' => 'health_record',
            'record_id' => $entry['id'],
            'occurred_at' => $occurredAt->toDateString(),
            'record_type' => $entry['record_type'],
            'title' => $entry['title'],
            'weight' => $entry['weight'],
        ];
    }
}
