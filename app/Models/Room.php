<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'slug',
        'description',
        'type',
        'capacity',
        'sleeps',
        'bedrooms',
        'bathrooms',
        'is_private',
        'status',
        'base_rate',
        'min_stay',
        'max_stay',
        'features',
        'sort_order',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'sleeps' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'is_private' => 'boolean',
        'base_rate' => 'decimal:2',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'features' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): ?RoomImage
    {
        return $this->images()->where('is_primary', true)->first() ?? $this->images()->first();
    }

    /**
     * Bundled fallback photo used when the room has no uploaded images.
     */
    public function defaultImage(): ?string
    {
        $defaults = [
            'lion-suite' => 'images/bedroom-lion.png',
            'elephant-room' => 'images/bedroom-elephant.png',
            'buffalo-room' => 'images/bedroom-buffalo.png',
            'rhino-room' => 'images/bedroom-rhino.png',
            'leopard-room' => 'images/bedroom-leopard.png',
        ];

        return $defaults[$this->slug] ?? null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
