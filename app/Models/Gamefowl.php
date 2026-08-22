<?php

/**
 * File: app/Models/Gamefowl.php
 *
 * Purpose:
 *   Represents a single gamefowl (bird) owned by a user account. This is
 *   the central domain record of the project: health assessments and manual
 *   health records always belong to one bird, and ownership of a bird is
 *   what decides who may read/write its data.
 *
 * How it fits into the project:
 *   - GamefowlController provides the CRUD endpoints for this model and
 *     scopes every query through $user->gamefowls() so owners only ever
 *     see their own birds.
 *   - GamefowlPolicy is THE source of truth for "does this user own this
 *     bird?" — other policies (HealthAssessmentPolicy) delegate to it.
 *   - HealthHistoryController merges this bird's assessments + records
 *     into its timeline/status endpoints.
 *   - DiagnosticEngine results are persisted per-bird via
 *     HealthAssessment (see that model).
 *
 * Notes for new developers:
 *   - DELETE /gamefowls/{id} soft-deletes a bird (deleted_at stamped),
 *     so past health data survives. `is_active` is a separate, softer
 *     idea: an owner can mark a bird inactive/retired while keeping it
 *     listed under ?include_inactive=1.
 */

namespace App\Models;

use Database\Factories\GamefowlFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gamefowl extends Model
{
    /**
     * HasFactory: test fixtures via GamefowlFactory.
     * SoftDeletes: DELETE /gamefowls/{id} hides the bird but keeps the row,
     * protecting its assessment history (project-wide convention).
     */
    /** @use HasFactory<GamefowlFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Allowed values for the `sex` column, shared with validation
     * (StoreGamefowlRequest validates against this exact constant) so the
     * accepted values live in one place.
     */
    public const SEXES = ['male', 'female', 'unknown'];

    /**
     * Mass-assignable profile fields. `user_id` is deliberately NOT here:
     * birds are created through $user->gamefowls()->create(...) which sets
     * the foreign key from the authenticated user, making payload-spoofed
     * ownership impossible (tested in CreateGamefowlTest).
     */
    protected $fillable = [
        'name',
        'breed',
        'date_of_birth',
        'sex',
        'color',
        'weight',
        'date_acquired',
        'notes',
        'is_active',
    ];

    /**
     * Type conversions:
     * - dates become Carbon instances (so ->age below can do date math)
     * - weight is stored/read with two decimal places
     * - is_active arrives as a real boolean instead of 0/1
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_acquired' => 'date',
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Computed attribute: the bird's CURRENT age derived from date_of_birth.
     *
     * Why it exists: storing an age number would go stale as time passes,
     * so we store the birth date and calculate age on demand. Returned as
     * {years, months} (or null when the birth date is unknown) and included
     * in API output by GamefowlResource.
     */
    protected function age(): Attribute
    {
        return Attribute::get(function (): ?array {
            // No known birthday -> no meaningful age to report.
            if (! $this->date_of_birth) {
                return null;
            }

            // Difference between the birth date and right now gives the age.
            $interval = $this->date_of_birth->diff(now());

            return [
                'years' => $interval->y,
                'months' => $interval->m,
            ];
        });
    }

    /**
     * The owner account this bird belongs to (foreign key: user_id).
     * Named explicitly with its FK column because GamefowlPolicy compares
     * $gamefowl->user_id against the authenticated user for every
     * ownership decision in the app.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Symptom-based diagnostic assessments recorded for this bird.
     * Consumed by HealthAssessmentController (creation) and
     * HealthHistoryController (timeline + latest-for-status lookup).
     */
    public function healthAssessments(): HasMany
    {
        return $this->hasMany(HealthAssessment::class);
    }

    /**
     * Manual logbook entries (vet visits, weigh-ins, notes) for this bird.
     * Consumed by HealthRecordController and merged into the timeline by
     * HealthHistoryController alongside the assessments above.
     */
    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }
}
