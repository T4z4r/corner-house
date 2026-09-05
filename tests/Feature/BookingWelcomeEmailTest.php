<?php

namespace Tests\Feature;

use App\Mail\GuestCommunicationMail;
use App\Models\CommunicationTemplate;
use App\Models\Reservation;
use App\Services\Notification\NotificationService;
use Database\Seeders\CommunicationTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingWelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_confirmation_template_contains_the_welcome_message(): void
    {
        $this->seed([SettingsSeeder::class, CommunicationTemplateSeeder::class]);

        $template = CommunicationTemplate::where('slug', 'booking_confirmation')->firstOrFail();

        $this->assertStringContainsString('FINDING THE HOUSE', $template->body);
        $this->assertStringContainsString('Network: Corner House', $template->body);
        $this->assertStringContainsString('Hakunamatata', $template->body);
        $this->assertStringContainsString('07756 142487', $template->body);
        $this->assertStringContainsString('{{guest_name}}', $template->body);
    }

    public function test_welcome_message_is_sent_to_guest_after_booking_confirmation(): void
    {
        $this->seed([SettingsSeeder::class, CommunicationTemplateSeeder::class]);
        Mail::fake();

        $reservation = Reservation::factory()->create();
        $guestEmail = $reservation->guest->email;

        app(NotificationService::class)->sendForEvent('booking_confirmation', $reservation);

        $this->assertDatabaseHas('communications', [
            'reservation_id' => $reservation->id,
            'status' => 'sent',
        ]);

        Mail::assertSent(GuestCommunicationMail::class, function (GuestCommunicationMail $mail) use ($guestEmail): bool {
            return $mail->hasTo($guestEmail)
                && str_contains($mail->emailBody, 'FINDING THE HOUSE')
                && str_contains($mail->emailBody, 'Hakunamatata')
                && str_contains($mail->emailBody, '07756 142487');
        });
    }
}
