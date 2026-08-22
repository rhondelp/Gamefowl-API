<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File: app/Models/HealthRecord.php
 *
 * Purpose:
 *   A MANUAL, human-entered health logbook entry for a bird — a vet visit,
 *   a weight check, a vaccination, or a free-form note. This is deliberately
 *   separate from HealthAssessment: assessments are system-generated
 *   diagnostic events produced by the expert engine, while records are
 *   typed in by the owner with an arbitrary (possibly backdated) date.
 *
 * How it fits into the project:
 *   - Created/listed by HealthRecordController (owner-scoped via the bird).
 *   - Merged into the per-bird timeline by HealthHistoryController and used
 *     as the "latest_health_record" context in the status endpoint.
 */

class HealthRecord extends Model
{
    use HasFactory;

    /**
     * The fixed set of entry types. Kept small on purpose — this is a simple
     * logbook, not a taxonomy. StoreHealthRecordRequest validates against
     * this constant.
     */
    public const TYPES = [
        'vet_visit',
        'weight_check',
        'general_note',
        'vaccination',
    ];

    protected $fillable = [
        'gamefowl_id',
        'recorded_at',
        'type',
        'title',
        'notes',
        'weight',
    ];

    /**
     * Type conversions:
     * - recorded_at becomes a Carbon date (timeline sorting relies on it)
     * - weight is exposed with two decimal places like gamefowl weights
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'date',
            'weight' => 'decimal:2',
        ];
    }

    /**
     * The bird this logbook entry describes. All access is routed through
     * the owning bird so per-owner isolation applies automatically.
     */
    public function gamefowl(): BelongsTo
    {
        return $this->belongsTo(Gamefowl::class);
    }
}
