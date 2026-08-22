<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * File: app/Models/DiseaseSymptomRule.php
 *
 * Purpose:
 *   A single knowledge-base RULE: "disease X is indicated by symptom Y with
 *   importance weight W". One row per (disease, symptom) pair. This table
 *   IS the expert system's brain — DiagnosticEngine sums these weights to
 *   produce match scores, and admins manage rows through /admin/rules.
 *
 * Notes for new developers:
 *   - Extends Pivot (Laravel's class for belongsToMany intermediate rows)
 *     because this model exists as the `using()` target of the
 *     Disease<->Symptom relationship.
 *   - $incrementing MUST stay true: Laravel's base Pivot assumes a
     *   non-incrementing key by default, but this table has its own `id`
 *   column — without this flag, freshly created rules report id = null
 *   (a bug we hit and fixed during testing).
 */

class DiseaseSymptomRule extends Pivot
{
    /**
     * The allowed weight range for a rule. 5 = highly indicative of the
     * disease, 1 = weak/general support. Kept as constants here so the
     * validation rules in StoreRuleRequest/UpdateRuleRequest and any future
     * UI all reference one source of truth.
     */
    public const WEIGHT_MIN = 1;
    public const WEIGHT_MAX = 5;

    public $incrementing = true;

    protected $table = 'disease_symptom_rules';

    protected $fillable = [
        'disease_id',
        'symptom_id',
        'weight',
    ];

    /**
     * Weight is stored as a tiny integer but always handled as a PHP int so
     * score arithmetic never mixes strings with numbers.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'integer',
        ];
    }

    /**
     * The disease side of this rule. Used when inspecting individual rules.
     */
    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    /**
     * The symptom side of this rule. Used when inspecting individual rules.
     */
    public function symptom(): BelongsTo
    {
        return $this->belongsTo(Symptom::class);
    }
}
