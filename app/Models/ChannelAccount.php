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
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(ChannelMapping::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(ChannelSyncLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
