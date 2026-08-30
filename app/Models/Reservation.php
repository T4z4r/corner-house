<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'property_id',
        'room_id',
        'guest_id',
        'check_in',
        'check_out',
        'guests_count',
        'status',
        'source',
        'channel',
        'external_channel',
        'external_booking_id',
        'base_amount',
        'discount_amount',
        'tax_amount',
        'fees_amount',
        'total_amount',
        'paid_amount',
        'payment_status',
        'sync_status',
        'sync_attempts',
        'notes',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'guests_count' => 'integer',
        'base_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'fees_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'sync_attempts' => 'array',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancellation_reason' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    /**
     * Whether the reservation occupies a date range (excludes cancelled).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['cancelled', 'no_show']);
    }

    /**
     * Scope for reservations/spectacular with an overlapping date range on a room.
     */
    public function scopeOverlapsDates(Builder $query, $roomId, $checkIn, $checkOut): Builder
    {
        return $query
            ->where('room_id', $roomId)
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn);
    }

    public static function generateReference(): string
    {
        $reference = 'CH-'.strtoupper(Str::random(6));

        while (static::where('reference', $reference)->exists()) {
            $reference = 'CH-'.strtoupper(Str::random(6));
        }

        return $reference;
    }
}
