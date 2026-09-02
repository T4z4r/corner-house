<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'cast',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => cache()->forget('settings.all'));
        static::deleted(fn () => cache()->forget('settings.all'));
    }

    /**
     * Cast a raw value from its stored string form based on a cast type.
     */
    public static function castRawValue(mixed $value, ?string $cast): mixed
    {
        return match ($cast) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'json' => json_decode((string) $value, true),
            'secret' => self::decryptSecret($value),
            default => $value,
        };
    }

    /**
     * Cast a value from its stored string form based on the column cast.
     */
    public function castValue(): mixed
    {
        return self::castRawValue($this->value, $this->cast);
    }

    /**
     * Get the value of a setting by key, with an optional default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $all = self::allCached();
        $setting = $all->firstWhere('key', $key);

        if (! $setting) {
            return $default;
        }

        $value = self::castRawValue($setting['value'], $setting['cast']);

        return $value === null ? $default : $value;
    }

    /**
     * All settings cached for the lifetime of a single request cycle.
     *
     * Returns a collection of plain arrays (key, value, cast) to avoid
     * serialising Eloquent model instances in the cache.
     */
    public static function allCached(bool $fresh = false): Collection
    {
        if (! Schema::hasTable('settings')) {
            return collect();
        }

        $rows = cache()->remember('settings.all', now()->addMinutes(5), function (): array {
            return self::query()->get(['key', 'value', 'cast'])->map(fn (Setting $setting): array => [
                'key' => $setting->key,
                'value' => $setting->value,
                'cast' => $setting->cast,
            ])->all();
        });

        $rows = (array) $rows;

        return collect($rows);
    }

    public function isSecret(): bool
    {
        return $this->cast === 'secret';
    }

    public function hasStoredSecret(): bool
    {
        return $this->isSecret() && filled($this->value);
    }

    public static function encryptSecret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    private static function decryptSecret(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
}
