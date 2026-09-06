<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelRateMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_account_id',
        'external_property_id',
        'hotel_id',
        'hotel_name',
        'raw_xml',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class, 'channel_account_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ChannelRate::class);
    }
}
