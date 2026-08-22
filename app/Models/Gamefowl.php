<?php

namespace App\Models;

use Database\Factories\GamefowlFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gamefowl extends Model
{
    /** @use HasFactory<GamefowlFactory> */
    use HasFactory, SoftDeletes;

    public const SEXES = ['male', 'female', 'unknown'];

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

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_acquired' => 'date',
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected function age(): Attribute
    {
        return Attribute::get(function (): ?array {
            if (! $this->date_of_birth) {
                return null;
            }

            $interval = $this->date_of_birth->diff(now());

            return [
                'years' => $interval->y,
                'months' => $interval->m,
            ];
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
