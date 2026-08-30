<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::firstOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'damage_deposit'], ['value' => '0', 'group' => 'booking', 'label' => 'Deposit', 'cast' => 'decimal:2']);
    }

    public function test_search_lists_available_rooms_with_server_price(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'status' => 'active', 'capacity' => 2]);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        $this->get(route('booking.search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]))->assertOk()->assertSee($room->name)->assertSee('200.00');
    }

    public function test_details_page_rejects_unavailable_room(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'status' => 'active']);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 1,
            'status' => 'confirmed',
        ]);

        $this->get(route('booking.details', [
            'room' => $room,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 1,
        ]))->assertRedirect(route('booking.search'));
    }

    public function test_guest_can_hold_and_pay_then_confirm_from_session(): void
    {
        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active']);
        $checkIn = now()->addDays(14)->toDateString();
        $checkOut = now()->addDays(16)->toDateString();

        $response = $this->post(route('booking.pay'), [
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Guest',
            'guest_email' => 'alex@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reservations', [
            'source' => 'direct',
            'status' => 'hold',
            'payment_status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('payments', ['status' => 'pending']);

        $payment = Payment::query()->first();
        $this->get(route('booking.confirmation', ['session_id' => $payment->provider_session_id]))
            ->assertOk()
            ->assertSee('Booking confirmed');

        $this->assertDatabaseHas('reservations', [
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $this->assertSame(1, Reservation::query()->count());
    }

    public function test_api_calculates_price_and_creates_hold(): void
    {
        $room = Room::factory()->create(['base_rate' => 50, 'status' => 'active']);
        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(22)->toDateString();

        $this->postJson('/api/v1/booking/calculate-price', [
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 1,
        ])->assertOk()->assertJsonPath('total', 100);

        $this->postJson('/api/v1/booking/hold', [
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'session_id' => 'sess-1',
        ])->assertOk()->assertJsonStructure(['hold_token', 'expires_at']);

        $this->assertDatabaseHas('booking_holds', ['room_id' => $room->id, 'status' => 'active']);
    }

    public function test_minimum_stay_rule_blocks_short_booking(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'min_stay' => 3, 'status' => 'active']);

        $this->post(route('booking.pay'), [
            'room_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(11)->toDateString(),
            'guests_count' => 1,
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Guest',
            'guest_email' => 'alex@example.com',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_maximum_stay_rule_blocks_long_booking(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'max_stay' => 2, 'status' => 'active']);
        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Short stay season',
            'rule_type' => 'seasonal',
            'start_date' => now()->addDays(8),
            'end_date' => now()->addDays(20),
            'max_stay' => 2,
            'adjustment_type' => 'amount',
            'adjustment_value' => 0,
            'priority' => 5,
        ]);

        $this->post(route('booking.pay'), [
            'room_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(13)->toDateString(),
            'guests_count' => 1,
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Guest',
            'guest_email' => 'alex@example.com',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('reservations', 0);
    }
}
