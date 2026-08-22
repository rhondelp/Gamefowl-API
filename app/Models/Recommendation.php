<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * File: app/Models/Recommendation.php
 *
 * Purpose:
 *   A piece of care advice the system can attach to diseases — e.g.
 *   "Isolate affected birds immediately" or "Provide clean water with
 *   electrolytes". Recommendations are what the mobile app shows an owner
 *   alongside a possible condition.
 *
 * How it fits into the project:
 *   - Admins manage recommendations via Admin\RecommendationController and
 *     link them to diseases through Admin\DiseaseController
 *     (attach/detach endpoints).
 *   - They are NOT part of the diagnostic score; they ride along with
 *     results purely as guidance content.
 */

class Recommendation extends Model
{
    use HasFactory;

    /**
     * Fixed, extensible category list used to group advice in the mobile
     * app. Constrained here (rather than fully free-form) so grouping stays
     * predictable; adding a new category is a one-line change to this
     * constant, and StoreRecommendationRequest validates against it.
     */
    public const CATEGORIES = [
        'hygiene',
        'isolation',
        'nutrition',
        'monitoring',
        'medication',
        'vaccination',
        'environment',
    ];

    protected $fillable = [
        'title',
        'content',
        'category',
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
     * Query scope limiting results to active recommendations.
     *
     * Mirrors scopeActive() on Disease/Symptom: deactivated advice stops
     * appearing for owners while keeping referential integrity intact.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Diseases this piece of advice is linked to (via the
     * disease_recommendations pivot). Used when admins attach/detach and
     * when assessment output assembles guidance per suggested disease.
     */
    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class, 'disease_recommendations')
            ->withTimestamps();
    }
}
