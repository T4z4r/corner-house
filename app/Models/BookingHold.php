<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_id',
        'check_in',
        'check_out',
        'session_id',
        'hold_token',
        'status',
        'quoted_total',
        'expires_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'quoted_total' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Active (non-expired, non-released) holds only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    /**
     * Holds that overlap a given date range for a room.
     */
    public function scopeOverlapsDates(Builder $query, $roomId, $checkIn, $checkOut): Builder
    {
        return $query
            ->where('room_id', $roomId)
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn);
    }

    protected static function booted(): void
    {
        static::creating(function (BookingHold $hold): void {
            $hold->hold_token ?: $hold->hold_token = (string) Str::uuid();
        });
    }
}
