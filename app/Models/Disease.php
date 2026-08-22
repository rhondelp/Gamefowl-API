<?php

/**
 * File: app/Models/Disease.php
 *
 * Purpose:
 *   A condition the expert system can suggest (e.g. "Coccidiosis",
 *   "Newcastle Disease"). A disease carries the informational content shown
 *   to owners (description, recommended action, prevention tips, optional
 *   vet warning) and is linked to symptoms through WEIGHTED RULES — those
 *   weights are what the DiagnosticEngine scores against.
 *
 * How it fits into the project:
 *   - Admins manage diseases via Admin\DiseaseController (including
 *     attaching recommendations).
 *   - Owners read ACTIVE diseases via DiseaseController (weights hidden).
 *   - DiagnosticEngine::diagnose() loads active diseases + their active
 *     symptom rules and computes match scores.
 *   - HealthAssessmentResult stores snapshot copies of disease data per
 *     assessment, so this model's later edits never rewrite history.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Disease extends Model
{
    use HasFactory;

    /**
     * Allowed severity levels for diseases. Note this list has one extra
     * value compared to Symptom::SEVERITIES — "critical" — reserved for
     * the most dangerous conditions (like Newcastle Disease). Validated by
     * the admin disease Form Requests.
     */
    public const SEVERITIES = ['mild', 'moderate', 'severe', 'critical'];

    protected $fillable = [
        'name',
        'description',
        'severity',
        'general_info',
        'recommended_action',
        'prevention_info',
        'vet_warning',
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
     * Query scope limiting results to active diseases.
     *
     * Used by DiseaseController (owner-facing reads) and by
     * DiagnosticEngine::diagnose() so deactivated conditions can never be
     * suggested again — while historical assessment snapshots stay intact.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The weighted rule set connecting this disease to its symptoms.
     *
     * This relationship IS the knowledge base: each pivot row contributes a
     * `weight` (1–5) that DiagnosticEngine sums into match scores. Uses the
     * DiseaseSymptomRule pivot model so weights are strongly typed ints.
     */
    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class, 'disease_symptom_rules')
            ->using(DiseaseSymptomRule::class)
            ->withPivot('id', 'weight')
            ->withTimestamps();
    }

    /**
     * Care advice attached to this disease (isolation steps, water with
     * electrolytes, etc.). Attached/detached by admins through
     * Admin\DiseaseController and shown alongside assessment results.
     */
    public function recommendations(): BelongsToMany
    {
        return $this->belongsToMany(Recommendation::class, 'disease_recommendations')
            ->withTimestamps();
    }
}
