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

    /**
     * Whether this account can be picked up by the scheduled syncs. Eligibility
     * depends on having usable tokens, not on the current status — an account
     * left in 'error' after a transient API/auth failure must still be retried
     * on the next run so the integration can self-heal without manual re-activation.
     */
    public function isSyncEligible(): bool
    {
        $credentials = $this->credentials ?? [];

        if (! empty($credentials['refresh_token']) || ! empty($credentials['access_token']) || ! empty($credentials['invite_code'])) {
            return true;
        }

        $fallback = config('services.beds24.refresh_token') ?: config('services.beds24.invite_code');

        return is_string($fallback) && $fallback !== '';
    }
}
