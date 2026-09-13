<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelPricingSnapshot extends Model
{
    protected $fillable = [
        'channel_account_id',
        'room_id',
        'external_room_id',
        'rate_code',
        'date_from',
        'date_to',
        'raw_data',
        'rows',
        'open_days',
        'closed_days',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'rows' => 'array',
            'open_days' => 'integer',
            'closed_days' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class, 'channel_account_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
