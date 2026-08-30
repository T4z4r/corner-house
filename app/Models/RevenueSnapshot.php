<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'snapshot_date',
        'revenue',
        'occupancy_pct',
        'adr',
        'revpar',
        'bookings_count',
        'cancellations_count',
        'direct_bookings',
        'ota_bookings',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'revenue' => 'decimal:2',
        'occupancy_pct' => 'decimal:2',
        'adr' => 'decimal:2',
        'revpar' => 'decimal:2',
        'bookings_count' => 'integer',
        'cancellations_count' => 'integer',
        'direct_bookings' => 'integer',
        'ota_bookings' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
