<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Booking\BookingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingService::class);

        Setting::firstOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'damage_deposit'], ['value' => '0', 'group' => 'booking', 'label' => 'Deposit', 'cast' => 'decimal:2']);
    }

    private function makeRoom(array $attributes = []): Room
    {
        $property = Property::factory()->create();

        return Room::factory()->create(array_merge([
            'property_id' => $property->id,
            'base_rate' => 100,
        ], $attributes));
    }

    private function basePayload(Room $room, array $overrides = []): array
    {
        $checkIn = Carbon::now()->addDays(10)->startOfDay();
        $checkOut = $checkIn->copy()->addDays(2);

        return array_merge([
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => 2,
            'guest_email' => 'lead@example.com',
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Smith',
            'status' => 'confirmed',
            'total_amount_sent_by_browser' => 1, // must be ignored
        ], $overrides);
    }

    public function test_creates_reservation_with_server_side_pricing(): void
    {
        $room = $this->makeRoom(['base_rate' => 100]);

        $result = $this->service->create($this->basePayload($room));

        $reservation = $result['reservation'];

        $this->assertDatabaseHas('reservations', ['reference' => $reservation->reference]);
        // Total is calculated by the engine (2 nights x 100 = 200), not the browser.
        $this->assertSame(200.0, (float) $reservation->total_amount);
        $this->assertSame('confirmed', $reservation->status);
    }

    /**
     * The critical double-booking protection test:
     * a second overlapping booking for the same room MUST fail.
     */
    public function test_overlapping_booking_for_same_room_fails(): void
    {
        $room = $this->makeRoom();
        $payload = $this->basePayload($room);

        $this->service->create($payload);

        $this->expectException(DomainException::class);
        $this->service->create($payload);

        // Only ONE reservation for that room/date range must exist.
        $this->assertSame(
            1,
            Reservation::query()->where('room_id', $room->id)->count(),
        );
    }

    public function test_non_overlapping_booking_on_different_dates_is_allowed(): void
    {
        $room = $this->makeRoom();

        $this->service->create($this->basePayload($room));

        $later = Carbon::now()->addDays(20)->startOfDay();
        $result = $this->service->create($this->basePayload($room, [
            'check_in' => $later->toDateString(),
            'check_out' => $later->copy()->addDays(2)->toDateString(),
        ]));

        $this->assertSame('confirmed', $result['reservation']->status);
        $this->assertSame(2, Reservation::query()->where('room_id', $room->id)->count());
    }

    public function test_checkout_must_be_after_checkin(): void
    {
        $room = $this->makeRoom();

        $this->expectException(DomainException::class);
        $this->service->create($this->basePayload($room, [
            'check_out' => Carbon::now()->addDays(10)->toDateString(),
        ]));
    }

    public function test_external_booking_is_idempotent(): void
    {
        $room = $this->makeRoom();

        $payload = $this->basePayload($room, [
            'external_channel' => 'airbnb',
            'external_booking_id' => 'AB12345',
        ]);

        $first = $this->service->create($payload)['reservation'];

        // A duplicate webhook payload must not create a second reservation;
        // it returns the existing reservation.
        $second = $this->service->create($payload)['reservation'];

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Reservation::query()->where('room_id', $room->id)->count());
    }
}
