<?php

namespace Tests\Feature;

use App\Mail\NewEnquiryNotificationMail;
use App\Models\Enquiry;
use App\Models\Setting;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewEnquiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    public function test_booking_enquiry_sends_notification_email_to_configured_recipient(): void
    {
        $recipient = 'owner@example.com';
        Setting::where('key', 'admin_notification_email')->firstOrFail()->update(['value' => $recipient]);
        Setting::where('key', 'booking_notify_email')->firstOrFail()->update(['value' => $recipient]);

        Mail::fake();

        $this->postJson(route('booking.enquiry'), [
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'phone' => '07700 900123',
            'guests' => '12',
            'checkIn' => '2026-10-02',
            'checkOut' => '2026-10-04',
            'nights' => 2,
            'message' => 'A birthday weekend.',
            'drinksPackage' => true,
            'acceptedTerms' => true,
        ])->assertOk()->assertJson(['status' => 'ok']);

        Mail::assertSent(NewEnquiryNotificationMail::class, function (NewEnquiryNotificationMail $mail) use ($recipient): bool {
            return $mail->hasTo($recipient)
                && str_contains($mail->envelope()->subject, 'New booking enquiry from Sam Guest')
                && str_contains($mail->emailBody, 'sam@example.com')
                && str_contains($mail->emailBody, 'A birthday weekend.');
        });
    }

    public function test_contact_enquiry_sends_notification_email_to_configured_recipient(): void
    {
        $recipient = 'owner@example.com';
        Setting::where('key', 'admin_notification_email')->firstOrFail()->update(['value' => $recipient]);
        Setting::where('key', 'booking_notify_email')->firstOrFail()->update(['value' => $recipient]);

        Mail::fake();

        $this->post(route('contact.submit'), [
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'message' => 'Can we arrive early?',
        ])->assertRedirect();

        Mail::assertSent(NewEnquiryNotificationMail::class, function (NewEnquiryNotificationMail $mail) use ($recipient): bool {
            return $mail->hasTo($recipient)
                && str_contains($mail->envelope()->subject, 'New website enquiry from Sam Guest')
                && str_contains($mail->emailBody, 'Can we arrive early?');
        });
    }

    public function test_notification_not_sent_when_recipient_email_is_empty(): void
    {
        Setting::where('key', 'admin_notification_email')->firstOrFail()->update(['value' => '']);
        Setting::where('key', 'booking_notify_email')->firstOrFail()->update(['value' => '']);

        Mail::fake();

        $this->postJson(route('booking.enquiry'), [
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'message' => 'Hello',
        ])->assertOk();

        Mail::assertNothingSent();
    }

    public function test_mailable_body_contains_enquiry_details(): void
    {
        $enquiry = Enquiry::factory()->create([
            'type' => Enquiry::TYPE_BOOKING,
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'phone' => '07700 900123',
            'guests' => '12',
            'check_in' => '2026-10-02',
            'check_out' => '2026-10-04',
            'nights' => 2,
            'message' => 'A birthday weekend.',
            'drinks_package' => true,
            'terms_accepted' => true,
        ]);

        $mailable = new NewEnquiryNotificationMail($enquiry);

        $mailable->assertSeeInHtml('Sam Guest');
        $mailable->assertSeeInHtml('sam@example.com');
        $mailable->assertSeeInHtml('A birthday weekend.');
        $mailable->assertSeeInHtml('Nights: 2');
    }
}
