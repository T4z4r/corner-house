<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'event',
        'channel',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }
}
