<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'start_date',
        'end_date',
        'rate',
        'minimum_stay',
        'is_enabled',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'rate' => 'decimal:2',
        'minimum_stay' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
