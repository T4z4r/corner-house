<?php

namespace App\Models;

use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    public const TYPE_BOOKING = 'booking';

    public const TYPE_CONTACT = 'contact';

    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'guests',
        'check_in',
        'check_out',
        'nights',
        'message',
        'drinks_package',
        'terms_accepted',
        'status',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'nights' => 'integer',
        'drinks_package' => 'boolean',
        'terms_accepted' => 'boolean',
    ];

    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }
}