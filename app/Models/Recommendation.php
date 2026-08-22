<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recommendation extends Model
{
    use HasFactory;

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

    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class, 'disease_recommendations')
            ->withTimestamps();
    }
}
