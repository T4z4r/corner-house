<?php

namespace App\Models;

use App\Models\Concerns\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory, HasHashId;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'address_line_1',
        'address_line_2',
        'city',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'capacity',
        'bedrooms',
        'bathrooms',
        'status',
        'is_primary',
        'linked_property_id',
        'currency',
        'smoking_allowed',
        'children_allowed',
        'parties_allowed',
        'pets_allowed',
        'check_in_from',
        'check_in_until',
        'check_out_from',
        'check_out_until',
        'custom_rules',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'capacity' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'smoking_allowed' => 'boolean',
        'children_allowed' => 'boolean',
        'parties_allowed' => 'boolean',
        'is_primary' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Property $property): void {
            $property->pets_allowed ??= 'no';
            $property->check_in_from ??= '15:00';
            $property->check_in_until ??= '18:00';
            $property->check_out_from ??= '08:00';
            $property->check_out_until ??= '12:00';
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(ChannelMapping::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities');
    }

    public function policies(): HasMany
    {
        return $this->hasMany(PropertyPolicy::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): ?PropertyImage
    {
        return $this->images()->where('is_primary', true)->first() ?? $this->images()->first();
    }

    public function linkedProperty(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'linked_property_id');
    }

    /**
     * Ids of the linked property group: this property plus its two-way
     * linked partner. Linked properties are duplicate listings of the same
     * accommodation, so they share one live inventory and pricing surface.
     *
     * @return array<int, int>
     */
    public function linkedPropertyIds(): array
    {
        $ids = [$this->id];

        if ($this->linked_property_id && (int) $this->linked_property_id !== $this->id) {
            $ids[] = (int) $this->linked_property_id;
        }

        return array_values(array_unique($ids));
    }
}
