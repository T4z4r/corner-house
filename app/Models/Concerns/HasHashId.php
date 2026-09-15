<?php

namespace App\Models\Concerns;

use Hashids\Hashids;
use Illuminate\Database\Eloquent\Builder;

trait HasHashId
{
    /**
     * Get the encoded route key for this model.
     *
     * Replaces the default integer ID in URLs with an opaque hashids string
     * to prevent sequential ID enumeration by guests or bots.
     */
    public function getRouteKey(): string
    {
        return static::encodeHashId($this->getKey());
    }

    /**
     * Decode a hashed route-key value back to the underlying integer PK,
     * with a round-trip verification guard against raw-integer lookups.
     *
     * Supports explicit field bindings (e.g. `{room:hash_id}`) and the
     * default implicit binding (field is null → resolved to getRouteKeyName).
     */
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        $field = $field ?? $this->getRouteKeyName();

        // Only intercept when binding the primary key (hash-encoded), not
        // slug-based or other explicit fields.
        if ($field === $this->getKeyName() && is_string($value)) {
            $decoded = static::decodeHashId($value);

            if ($decoded === null) {
                // Invalid hash / round-trip guard failed — return empty
                // query so the route middleware produces a 404.
                return $query->whereRaw('0 = 1');
            }

            $value = $decoded;
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }

    /**
     * Encode an integer ID into an opaque hashids string.
     */
    public static function encodeHashId(int $id): string
    {
        return static::hashIds()->encode($id);
    }

    /**
     * Decode an opaque hashids string back to an integer.
     *
     * Returns null if the hash is invalid or fails the round-trip
     * verification (i.e. it was a raw integer like "5" that happened
     * to be a valid hashids encoding).
     */
    public static function decodeHashId(string $hash): ?int
    {
        if ($hash === '' || $hash[0] !== '-' && ctype_digit($hash)) {
            return null;
        }

        $decoded = static::hashIds()->decode($hash);

        if ($decoded === [] || ! isset($decoded[0])) {
            return null;
        }

        $int = (int) $decoded[0];

        // Round-trip verification: re-encode and compare.
        if (static::hashIds()->encode($int) !== $hash) {
            return null;
        }

        return $int;
    }

    protected static function hashIds(): Hashids
    {
        $salt = config('app.key', '').'|'.static::class;

        return new Hashids($salt, 10);
    }
}
