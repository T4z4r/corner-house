<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_id',
        'name',
        'rule_type',
        'start_date',
        'end_date',
        'priority',
        'adjustment_type',
        'adjustment_value',
        'minimum_stay',
        'max_stay',
        'occupancy_threshold',
        'days_before_checkin',
        'apply_weekends_only',
        'is_enabled',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'priority' => 'integer',
        'adjustment_value' => 'decimal:2',
        'minimum_stay' => 'integer',
        'max_stay' => 'integer',
        'occupancy_threshold' => 'float',
        'days_before_checkin' => 'integer',
        'apply_weekends_only' => 'boolean',
        'is_enabled' => 'boolean',
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
