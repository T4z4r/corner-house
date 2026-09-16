<?php

namespace App\Models;

use App\Models\Concerns\HasHashId;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory, HasHashId;

    public const TYPE_BOOKING = 'booking';

    public const TYPE_CONTACT = 'contact';

    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'room_id',
        'guests',
        'check_in',
        'check_out',
        'nights',
        'message',
        'drinks_package',
        'terms_accepted',
        'status',
        'reservation_id',
        'booking_hold_id',
    ];

    protected $casts = [
        'check_in' => 'date:Y-m-d',
        'check_out' => 'date:Y-m-d',
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

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bookingHold(): BelongsTo
    {
        return $this->belongsTo(BookingHold::class);
    }
}
