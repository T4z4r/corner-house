<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public const STATUS_APPROVED = 'approved';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'stars',
        'quote',
        'cite',
        'status',
        'sort_order',
        'source',
        'source_id',
    ];

    protected $casts = [
        'stars' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopeApproved($query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }
}
