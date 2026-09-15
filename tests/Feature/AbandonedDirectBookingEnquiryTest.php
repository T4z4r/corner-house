<?php

namespace Tests\Feature;

use App\Jobs\ExpireBookingHoldsJob;
use App\Models\Enquiry;
use App\Models\Property;
use App\Models\Room;
use App\Services\Booking\BookingHoldService;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AbandonedDirectBookingEnquiryTest extends TestCase
{
    use RefreshDatabase;

    private BookingHoldService $holds;

    private BookingService $bookings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->holds = app(BookingHoldService::class);
        $this->bookings = app(BookingService::class);
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

    private function abandonedHoldReservation(): array
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->dates();

        $hold = $this->holds->createHold($room->id, $checkIn, $checkOut, 'session-1', 200, 15)['hold'];

        $result = $this->bookings->create([
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => 2,
            'guest_email' => 'lead@example.com',
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Smith',
            'status' => 'hold',
            'source' => 'direct',
            'hold_token' => $hold->hold_token,
        ]);

        // Force the hold to have expired to simulate an abandoned checkout.
        $hold->forceFill(['expires_at' => now()->subMinute()])->save();

        return ['reservation' => $result['reservation'], 'room' => $room];
    }

    public function test_abandoned_direct_booking_is_saved_as_a_linked_enquiry(): void
    {
        $data = $this->abandonedHoldReservation();
        $reservation = $data['reservation'];

        $count = $this->holds->saveAbandonedDirectBookingsAsEnquiries();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('enquiries', [
            'type' => Enquiry::TYPE_BOOKING,
            'name' => 'Alex Smith',
            'email' => 'lead@example.com',
            'guests' => '2',
            'check_in' => $reservation->check_in->toDateString(),
            'check_out' => $reservation->check_out->toDateString(),
            'nights' => 2,
            'reservation_id' => $reservation->id,
        ]);

        $enquiry = Enquiry::where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertStringContainsString($reservation->reference, $enquiry->message);
        // The reservation itself is kept untouched.
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'hold', 'payment_status' => 'unpaid']);
    }

    public function test_abandoned_booking_link_is_stored_on_the_reservation(): void
    {
        $data = $this->abandonedHoldReservation();

        $this->assertNotNull($data['reservation']->booking_hold_id);
    }

    public function test_unexpired_hold_does_not_create_an_enquiry(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->dates();

        $hold = $this->holds->createHold($room->id, $checkIn, $checkOut, 'session-1', 200, 15)['hold'];

        $this->bookings->create([
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => 2,
            'guest_email' => 'lead@example.com',
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Smith',
            'status' => 'hold',
            'source' => 'direct',
            'hold_token' => $hold->hold_token,
        ]);

        $count = $this->holds->saveAbandonedDirectBookingsAsEnquiries();

        $this->assertSame(0, $count);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_non_direct_hold_reservations_are_ignored(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->dates();

        $hold = $this->holds->createHold($room->id, $checkIn, $checkOut, 'session-1', 200, 15)['hold'];

        $this->bookings->create([
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => 2,
            'status' => 'hold',
            'source' => 'manual',
            'hold_token' => $hold->hold_token,
        ]);

        $hold->forceFill(['expires_at' => now()->subMinute()])->save();

        $count = $this->holds->saveAbandonedDirectBookingsAsEnquiries();

        $this->assertSame(0, $count);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_paid_or_confirmed_direct_reservations_are_ignored(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->dates();

        $hold = $this->holds->createHold($room->id, $checkIn, $checkOut, 'session-1', 200, 15)['hold'];

        $result = $this->bookings->create([
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => 2,
            'guest_email' => 'lead@example.com',
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Smith',
            'status' => 'hold',
            'source' => 'direct',
            'hold_token' => $hold->hold_token,
        ]);

        $result['reservation']->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'paid_amount' => $result['reservation']->total_amount,
        ]);

        $hold->forceFill(['expires_at' => now()->subMinute()])->save();

        $count = $this->holds->saveAbandonedDirectBookingsAsEnquiries();

        $this->assertSame(0, $count);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_conversion_is_idempotent(): void
    {
        $this->abandonedHoldReservation();

        $this->holds->saveAbandonedDirectBookingsAsEnquiries();
        $count = $this->holds->saveAbandonedDirectBookingsAsEnquiries();

        $this->assertSame(0, $count);
        $this->assertDatabaseCount('enquiries', 1);
    }

    public function test_expire_booking_holds_job_creates_abandoned_enquiries(): void
    {
        $this->abandonedHoldReservation();

        $job = new ExpireBookingHoldsJob;
        $job->handle($this->holds);

        $this->assertDatabaseCount('enquiries', 1);
        $this->assertDatabaseHas('enquiries', ['name' => 'Alex Smith', 'email' => 'lead@example.com']);
    }
}
