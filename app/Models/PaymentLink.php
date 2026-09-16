<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'token',
        'expires_at',
        'used_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PaymentLink $paymentLink): void {
            $paymentLink->token ?: $paymentLink->token = (string) Str::uuid();
        });
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }
}
