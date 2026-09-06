<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_rate_map_id',
        'external_room_id',
        'room_name',
        'external_rate_id',
        'rate_name',
        'policy',
        'policy_id',
        'max_persons',
        'fixed_occupancy',
        'is_child_rate',
        'parent_rate_id',
        'follows_price',
        'percentage',
        'pricing_type',
        'meal_plan_code',
        'occupancy',
        'policies',
    ];

    protected $casts = [
        'max_persons' => 'integer',
        'fixed_occupancy' => 'integer',
        'is_child_rate' => 'boolean',
        'follows_price' => 'boolean',
        'percentage' => 'float',
        'occupancy' => 'array',
        'policies' => 'array',
    ];

    public function rateMap(): BelongsTo
    {
        return $this->belongsTo(ChannelRateMap::class, 'channel_rate_map_id');
    }
}
