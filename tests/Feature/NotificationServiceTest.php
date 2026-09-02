<?php

namespace Tests\Feature;

use App\Mail\GuestCommunicationMail;
use App\Models\Communication;
use App\Models\CommunicationTemplate;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::firstOrCreate(['key' => 'email_notifications_enabled'], ['value' => '1', 'group' => 'notifications', 'label' => 'Email notifications', 'cast' => 'boolean']);
        Setting::firstOrCreate(['key' => 'email_booking_confirmation_enabled'], ['value' => '1', 'group' => 'notifications', 'label' => 'Booking confirmation', 'cast' => 'boolean']);
    }

    private function makeReservation(): Reservation
    {
        $property = Property::factory()->create();
        $guest = Guest::factory()->create(['email' => 'guest@example.com']);

        return Reservation::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'confirmed',
        ]);
    }

    private function makeTemplate(string $event = 'booking_confirmation', string $channel = 'email'): CommunicationTemplate
    {
        return CommunicationTemplate::create([
            'name' => ucfirst($event),
            'slug' => Str::slug($event),
            'event' => $event,
            'channel' => $channel,
            'subject' => 'Your stay at {{property}}',
            'body' => 'Hello {{guest_name}}, welcome.',
            'is_active' => true,
        ]);
    }

    public function test_event_notification_is_not_sent_twice_for_same_reservation(): void
    {
        Mail::fake();

        $reservation = $this->makeReservation();
        $this->makeTemplate();

        app(NotificationService::class)->sendForEvent('booking_confirmation', $reservation);
        app(NotificationService::class)->sendForEvent('booking_confirmation', $reservation);

        Mail::assertSent(GuestCommunicationMail::class, 1);
        $this->assertDatabaseCount('communications', 1);
    }

    public function test_failed_delivery_can_be_retried(): void
    {
        $reservation = $this->makeReservation();
        $this->makeTemplate();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        app(NotificationService::class)->sendForEvent('booking_confirmation', $reservation);

        $this->assertDatabaseHas('communications', [
            'reservation_id' => $reservation->id,
            'status' => 'failed',
        ]);

        // A failure is NOT idempotent: retrying after the failure sends again.
        app(NotificationService::class)->sendForEvent('booking_confirmation', $reservation);

        $this->assertDatabaseCount('communications', 2);
    }

    public function test_non_email_channel_is_marked_failed_not_sent(): void
    {
        $reservation = $this->makeReservation();
        $this->makeTemplate('pre_arrival', 'sms');

        $communication = app(NotificationService::class)->sendForEvent('pre_arrival', $reservation);

        $this->assertInstanceOf(Communication::class, $communication);
        $this->assertSame('failed', $communication->status);
        $this->assertStringContainsString('Unsupported communication channel', (string) $communication->error_message);
    }
}
