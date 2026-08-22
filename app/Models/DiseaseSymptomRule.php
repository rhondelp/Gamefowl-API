<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DiseaseSymptomRule extends Pivot
{
    public const WEIGHT_MIN = 1;
    public const WEIGHT_MAX = 5;

    public $incrementing = true;

    protected $table = 'disease_symptom_rules';

    protected $fillable = [
        'disease_id',
        'symptom_id',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
        ];
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    public function symptom(): BelongsTo
    {
        return $this->belongsTo(Symptom::class);
    }
}
