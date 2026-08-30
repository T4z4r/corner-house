<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_account_id',
        'channel',
        'operation',
        'request',
        'response',
        'status',
        'error_message',
        'external_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class, 'channel_account_id');
    }
}
