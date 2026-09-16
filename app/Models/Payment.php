<?php

namespace App\Models;

use App\Models\Concerns\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, HasHashId;

    protected $fillable = [
        'reservation_id',
        'guest_id',
        'provider',
        'provider_session_id',
        'provider_payment_id',
        'amount',
        'currency',
        'status',
        'method',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isSecurityDeposit(): bool
    {
        return ($this->metadata['purpose'] ?? null) === 'security_deposit';
    }

    public function statusLabel(): string
    {
        if ($this->isSecurityDeposit()) {
            return match ($this->status) {
                'processing' => 'Held (not charged)',
                'cancelled' => 'Hold released / expired',
                default => ucfirst($this->status),
            };
        }

        return ucfirst($this->status);
    }
}
