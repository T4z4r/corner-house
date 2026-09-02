<?php

namespace Tests\Feature;

use App\Models\CalendarBlock;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IcalFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_ical_route_returns_calendar_feed(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertHeader('Content-Disposition')
            ->assertSee('BEGIN:VCALENDAR')
            ->assertSee('END:VCALENDAR')
            ->assertSee('PRODID:-//Corner House//Calendar Feed//EN')
            ->assertSee('VERSION:2.0');
    }

    public function test_ical_feed_includes_reservation_events(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);

        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(14)->toDateString();

        $guest = Guest::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $reservation = Reservation::factory()->create([
            'room_id' => $room->id,
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'confirmed',
        ]);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertSee('BEGIN:VEVENT')
            ->assertSee('UID:res-'.$reservation->id.'@corner-house')
            ->assertSee('SUMMARY:'.$reservation->reference.' - Jane Doe')
            ->assertSee('DTSTART;VALUE=DATE:'.str_replace('-', '', $checkIn))
            ->assertSee('DTEND;VALUE=DATE:'.str_replace('-', '', $checkOut));
    }

    public function test_ical_feed_excludes_cancelled_reservations(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);

        $reservation = Reservation::factory()->create([
            'room_id' => $room->id,
            'property_id' => $property->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(14)->toDateString(),
            'status' => 'cancelled',
        ]);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertDontSee('UID:res-'.$reservation->id.'@corner-house');
    }

    public function test_ical_feed_includes_blocking_calendar_blocks(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(22)->toDateString(),
            'type' => 'manual',
            'title' => 'Owner maintenance',
            'is_active' => true,
        ]);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertSee('SUMMARY:Owner maintenance')
            ->assertSee('STATUS:CONFIRMED')
            ->assertSee('TRANSP:OPAQUE');
    }

    public function test_ical_feed_excludes_non_blocking_blocks(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(22)->toDateString(),
            'type' => 'daily_price',
            'title' => 'Rate change',
            'is_active' => true,
        ]);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertDontSee('Rate change');
    }

    public function test_ical_feed_uses_date_format_with_day_shift(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);

        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(13)->toDateString();

        $reservation = Reservation::factory()->create([
            'room_id' => $room->id,
            'property_id' => $property->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'confirmed',
        ]);

        $response = $this->get(route('ical.room', $room));

        $endShifted = now()->addDays(14)->format('Ymd');

        $response->assertOk()
            ->assertSee('DTEND;VALUE=DATE:'.$endShifted);
    }

    public function test_ical_route_name_resolves_correctly(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $url = route('ical.room', $room);

        $this->assertStringContainsString('/ical/'.$room->id, $url);
    }

    public function test_ical_feed_does_not_require_authentication(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $this->assertGuest();
        $this->get(route('ical.room', $room))->assertOk();
    }

    public function test_ical_feed_respects_inactive_blocks(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(22)->toDateString(),
            'type' => 'manual',
            'title' => 'Disabled block',
            'is_active' => false,
        ]);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertDontSee('Disabled block');
    }

    public function test_ical_feed_includes_guest_count_in_description(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'name' => 'Garden Suite',
        ]);

        Reservation::factory()->create([
            'room_id' => $room->id,
            'property_id' => $property->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(14)->toDateString(),
            'status' => 'confirmed',
            'guests_count' => 3,
        ]);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertSee('Guests: 3')
            ->assertSee('Room: Garden Suite');
    }

    public function test_ical_feed_escapes_commas_in_text(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(22)->toDateString(),
            'type' => 'manual',
            'title' => 'Closed, maintenance',
            'is_active' => true,
        ]);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertSee('Closed\, maintenance');
    }

    public function test_ical_feed_sets_no_cache_header(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $response = $this->get(route('ical.room', $room));

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, must-revalidate');
    }
}
