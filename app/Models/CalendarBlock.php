<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarBlock extends Model
{
    use HasFactory;

    /**
     * Block types that make a room fully unavailable for the covered range
     * (availability closures, manual admin closures and channel-imported
     * closures). Remaining types (min_stay, max_stay, daily_price,
     * fixed_prices, multiplier) only influence stay rules or pricing.
     * Legacy aliases are included so older rows still block inventory.
     *
     * @var array<int, string>
     */
    public const INVENTORY_BLOCKING_TYPES = [
        'availability',
        'manual',
        'channel',
        'owner',
        'maintenance',
    ];

    protected $fillable = [
        'property_id',
        'room_id',
        'start_date',
        'end_date',
        'type',
        'title',
        'notes',
        'value',
        'min_stay',
        'max_stay',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'value' => 'decimal:2',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'is_active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Blocks that genuinely remove inventory from sale.
     */
    public function scopeBlockingInventory(Builder $query): Builder
    {
        return $query
            ->whereIn('type', self::INVENTORY_BLOCKING_TYPES)
            ->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
