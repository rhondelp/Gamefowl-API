<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    use HasFactory;

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

    protected function casts(): array
    {
        return [
            'recorded_at' => 'date',
            'weight' => 'decimal:2',
        ];
    }

    public function gamefowl(): BelongsTo
    {
        return $this->belongsTo(Gamefowl::class);
    }
}
