<?php

namespace Tests\Feature;

use App\Mail\NewBookingNotificationMail;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Booking\BookingService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewDirectBookingNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    public function test_new_direct_booking_sends_notification_email_to_configured_recipient(): void
    {
        $recipient = 'owner@example.com';
        Setting::where('key', 'admin_notification_email')->firstOrFail()->update(['value' => $recipient]);
        Setting::where('key', 'booking_notify_email')->firstOrFail()->update(['value' => $recipient]);

        Mail::fake();

        $reservation = $this->createDirectReservation();

        app(BookingService::class)->confirm($reservation);

        Mail::assertSent(NewBookingNotificationMail::class, function (NewBookingNotificationMail $mail) use ($reservation, $recipient): bool {
            return $mail->hasTo($recipient)
                && str_contains($mail->envelope()->subject, $reservation->reference)
                && str_contains($mail->emailBody, $reservation->reference)
                && str_contains($mail->emailBody, 'A new direct booking has been received');
        });
    }

    public function test_notification_not_sent_for_non_direct_bookings(): void
    {
        Mail::fake();

        $reservation = $this->createDirectReservation(source: 'manual');

        app(BookingService::class)->confirm($reservation);

        Mail::assertNothingSent();
    }

    public function test_notification_not_sent_when_recipient_email_is_empty(): void
    {
        Setting::where('key', 'admin_notification_email')->firstOrFail()->update(['value' => '']);
        Setting::where('key', 'booking_notify_email')->firstOrFail()->update(['value' => '']);

        Mail::fake();

        $reservation = $this->createDirectReservation();

        app(BookingService::class)->confirm($reservation);

        Mail::assertNothingSent();
    }

    public function test_mailable_body_contains_booking_details(): void
    {
        $reservation = $this->createDirectReservation();

        $mailable = new NewBookingNotificationMail($reservation);

        $mailable->assertSeeInHtml($reservation->reference);
        $mailable->assertSeeInHtml($reservation->check_in->format('d M Y'));
        $mailable->assertSeeInHtml($reservation->guest?->email ?? 'N/A');
        $mailable->assertSeeInHtml(number_format((float) $reservation->total_amount, 2));
    }

    private function createDirectReservation(string $source = 'direct'): Reservation
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'base_rate' => 100,
        ]);
        $guest = Guest::factory()->create();

        return Reservation::factory()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'source' => $source,
            'status' => 'hold',
            'check_in' => now()->addDays(10),
            'check_out' => now()->addDays(13),
            'total_amount' => 350,
        ]);
    }
}
