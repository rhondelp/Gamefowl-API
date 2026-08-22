<?php

/**
 * File: app/Models/Symptom.php
 *
 * Purpose:
 *   A sign an owner can report about their bird ("Bloody droppings",
 *   "Twisted neck", etc.). Symptoms are one half of the knowledge base:
 *   admins create them, owners select them when submitting a health
 *   assessment, and weighted rules tie them to diseases.
 *
 * How it fits into the project:
 *   - Admin\SymptomController manages them (create/update/deactivate).
 *   - Owners browse active symptoms via SymptomController to build a
 *     submission for POST /gamefowls/{id}/health-assessments.
 *   - StoreHealthAssessmentRequest validates that submitted symptom IDs
 *     exist AND are active — this is the layer responsible for that check
 *     (the DiagnosticEngine only ignores bad IDs defensively).
 *   - DiagnosticEngine matches submitted symptoms against disease rule sets.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Symptom extends Model
{
    use HasFactory;

    /**
     * Allowed severity levels for a single symptom. Deliberately narrower
     * than Disease::SEVERITIES (no 'critical') because an individual sign
     * alone is never critical — severity escalates at the disease level.
     */
    public const SEVERITIES = ['mild', 'moderate', 'severe'];

    protected $fillable = [
        'name',
        'description',
        'category',
        'severity',
        'is_active',
    ];

    /**
     * is_active becomes a real boolean in PHP/JSON output.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Query scope limiting results to active symptoms.
     *
     * Used by SymptomController (what owners may pick) and by
     * DiagnosticEngine's eager-load filter so rules pointing at deactivated
     * symptoms drop out of scoring entirely.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Diseases whose rule sets include this symptom (pivot carries the
     * weight). Mostly used by tests and admin tooling; the scoring engine
     * walks the relationship from the disease side.
     */
    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class, 'disease_symptom_rules')
            ->using(DiseaseSymptomRule::class)
            ->withPivot('id', 'weight')
            ->withTimestamps();
    }
}
