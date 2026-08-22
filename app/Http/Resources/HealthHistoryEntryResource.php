<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * File: app/Http/Resources/HealthHistoryEntryResource.php
 *
 * Purpose:
 *   Normalizes the two timeline source types (system-generated health
 *   assessments and manual health records) into ONE consistent entry shape,
 *   tagged with a `type` discriminator so the mobile app can render them
 *   differently.
 *
 * How it connects:
 *   HealthHistoryController builds pre-normalized entry arrays (one shape
 *   per source type, plus a sort tuple) and hands them to this resource for
 *   serialization. Assessment entries stay SUMMARIZED — the full diagnostic
 *   detail remains available via GET /health-assessments/{id}, referenced by
 *   assessment_id here instead of being duplicated into every timeline item.
 */
class HealthHistoryEntryResource extends JsonResource
{
    /**
     * Render one timeline entry according to its type.
     *
     * - 'assessment': top possible disease + score + severity summary.
     * - 'health_record': the logbook fields (type/title/weight).
     *
     * Both branches share `occurred_at`, but with different precision:
     * assessments use full ISO timestamps; records use their (possibly
     * backdated) plain date.
     *
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
