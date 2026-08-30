<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_id',
        'competitor',
        'date',
        'rate',
        'source',
        'captured_at',
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'decimal:2',
        'captured_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
