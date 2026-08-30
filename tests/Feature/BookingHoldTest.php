<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Room;
use App\Services\Booking\BookingHoldService;
use App\Services\Booking\BookingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingHoldTest extends TestCase
{
    use RefreshDatabase;

    private BookingHoldService $holds;

    private BookingService $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->holds = app(BookingHoldService::class);
        $this->booking = app(BookingService::class);
    }

    private function makeRoom(): Room
    {
        return Room::factory()->create([
            'property_id' => Property::factory()->create()->id,
            'base_rate' => 100,
        ]);
    }

    private function dates(): array
    {
        $checkIn = Carbon::now()->addDays(10)->startOfDay();

        return [$checkIn, $checkIn->copy()->addDays(2)];
    }

    public function test_create_hold_sets_expiry(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->dates();

        $result = $this->holds->createHold($room->id, $checkIn, $checkOut, 'session-1', 200, 15);

        $this->assertSame('active', $result['hold']->status);
        $this->assertTrue($result['expires_at']->isFuture());
        $this->assertNotNull($result['hold']->hold_token);
    }

    public function test_hold_blocks_booking_for_same_dates(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->dates();

        $this->holds->createHold($room->id, $checkIn, $checkOut, 'session-1', 200, 15);

        $this->expectException(DomainException::class);
        $this->booking->create([
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => 2,
            'status' => 'confirmed',
        ]);
    }

    public function test_expired_hold_is_released_and_no_longer_blocks(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->dates();

        $result = $this->holds->createHold($room->id, $checkIn, $checkOut, 'session-1', 200, 15);

        // Force expiry.
        $result['hold']->forceFill(['expires_at' => now()->subMinute()])->save();

        $released = $this->holds->expireExpiredHolds();
        $this->assertSame(1, $released);
        $this->assertSame('released', $result['hold']->fresh()->status);

        // Booking is now allowed after hold expiry.
        $booking = $this->booking->create([
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => 2,
            'status' => 'confirmed',
        ]);

        $this->assertSame('confirmed', $booking['reservation']->status);
    }
}
