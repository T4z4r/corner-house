<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'name',
        'status',
        'credentials',
        'settings',
        'last_synced_at',
        'last_error',
        'last_message_synced_at',
        'last_message_sync_status',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'last_synced_at' => 'datetime',
        'last_message_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
    ];

    /**
     * Keep diagnostic error messages bounded. Channel sync errors can carry
     * the full failing SQL (which embeds previous errors), so cap them to a
     * readable length rather than letting them grow unbounded.
     */
    public function setLastErrorAttribute(?string $value): void
    {
        $this->attributes['last_error'] = $value === null
            ? null
            : mb_substr($value, 0, 5000);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(ChannelMapping::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(ChannelSyncLog::class);
    }

    public function rateMaps(): HasMany
    {
        return $this->hasMany(ChannelRateMap::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
