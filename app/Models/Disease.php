<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Disease extends Model
{
    use HasFactory;

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

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class, 'disease_symptom_rules')
            ->using(DiseaseSymptomRule::class)
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function recommendations(): BelongsToMany
    {
        return $this->belongsToMany(Recommendation::class, 'disease_recommendations')
            ->withTimestamps();
    }
}
