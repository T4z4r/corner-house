<?php

namespace Tests\Feature;

use App\Jobs\PushBeds24BookingJob;
use App\Mail\GuestCommunicationMail;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use Database\Seeders\CommunicationTemplateSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_webhook_marks_payment_paid_and_confirms_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        $payment = Payment::factory()->create([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_amount,
            'status' => 'pending',
            'provider_session_id' => 'cs_test_webhook',
        ]);

        $this->postJson('/webhooks/stripe', [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_webhook',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_webhook',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'provider_payment_id' => 'pi_test_webhook',
        ]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    public function test_paid_hold_reservation_is_pushed_to_beds24_on_payment_confirmation(): void
    {
        Queue::fake([PushBeds24BookingJob::class]);

        $reservation = Reservation::factory()->create([
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        Payment::factory()->create([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_amount,
            'status' => 'pending',
            'provider_session_id' => 'cs_test_push_hold',
        ]);

        $this->postJson('/webhooks/stripe', [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_push_hold',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_push_hold',
                ],
            ],
        ])->assertOk();

        Queue::assertPushed(PushBeds24BookingJob::class, fn (PushBeds24BookingJob $job): bool => $job->reservationId === $reservation->id);
    }

    public function test_paid_already_confirmed_reservation_is_pushed_to_beds24_on_payment_confirmation(): void
    {
        Queue::fake([PushBeds24BookingJob::class]);

        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        Payment::factory()->create([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_amount,
            'status' => 'pending',
            'provider_session_id' => 'cs_test_push_confirmed',
        ]);

        $this->postJson('/webhooks/stripe', [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_push_confirmed',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_push_confirmed',
                ],
            ],
        ])->assertOk();

        Queue::assertPushed(PushBeds24BookingJob::class, fn (PushBeds24BookingJob $job): bool => $job->reservationId === $reservation->id);
    }

    public function test_browser_redirect_does_not_confirm_unpaid_session(): void
    {
        $gateway = app(PaymentGatewayInterface::class);
        $gateway->paid = false;

        $reservation = Reservation::factory()->create([
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        Payment::factory()->create([
            'reservation_id' => $reservation->id,
            'status' => 'pending',
            'provider_session_id' => 'cs_unpaid',
        ]);

        $this->get(route('booking.confirmation', ['session_id' => 'cs_unpaid']))
            ->assertOk()
            ->assertDontSee('Booking confirmed');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'hold',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_stripe_webhook_completes_payment_from_payment_intent(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        Payment::factory()->create([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_amount,
            'status' => 'pending',
            'provider_payment_id' => 'pi_test_webhook_intent',
        ]);

        $this->postJson('/webhooks/stripe', [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_webhook_intent',
                    'status' => 'succeeded',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $this->paymentId('pi_test_webhook_intent'),
            'status' => 'paid',
            'provider_payment_id' => 'pi_test_webhook_intent',
        ]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    public function test_direct_payment_intent_must_be_succeeded_to_confirm(): void
    {
        $gateway = app(PaymentGatewayInterface::class);
        $gateway->paid = false;

        $reservation = Reservation::factory()->create([
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        Payment::factory()->create([
            'reservation_id' => $reservation->id,
            'status' => 'pending',
            'provider_payment_id' => 'pi_test_unpaid_intent',
        ]);

        $this->postJson(route('booking.checkout.confirm', $reservation->getRouteKey()), [
            'payment_intent_id' => 'pi_test_unpaid_intent',
        ])->assertUnprocessable()->assertJsonStructure(['error']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'hold',
            'payment_status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('payments', [
            'provider_payment_id' => 'pi_test_unpaid_intent',
            'status' => 'pending',
        ]);
    }

    private function paymentId(string $providerPaymentId): int
    {
        return Payment::query()->where('provider_payment_id', $providerPaymentId)->value('id');
    }

    public function test_finance_manager_can_refund_paid_payment(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Finance Manager'));

        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        $payment = Payment::factory()->paid()->create([
            'reservation_id' => $reservation->id,
            'amount' => 150,
        ]);

        $this->actingAs($user)
            ->withConfirmedPassword()
            ->post(route('admin.payments.refund', $payment), [
                'amount' => 150,
                'reason' => 'Guest cancelled',
            ])->assertRedirect();

        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
    }

    public function test_refunding_an_already_refunded_payment_does_not_call_gateway_again(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('refund')->once()->andReturn(['id' => 're_test_1', 'status' => 'succeeded']);
        $this->app->instance(PaymentGatewayInterface::class, $gateway);

        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Finance Manager'));

        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        $payment = Payment::factory()->paid()->create([
            'reservation_id' => $reservation->id,
            'amount' => 150,
        ]);

        $this->actingAs($user)
            ->withConfirmedPassword()
            ->post(route('admin.payments.refund', $payment), ['amount' => 150])
            ->assertRedirect();

        $this->actingAs($user)
            ->withConfirmedPassword()
            ->post(route('admin.payments.refund', $payment), ['amount' => 150])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseCount('refunds', 1);
    }

    public function test_refunding_a_paid_payment_sends_a_refund_email_to_the_guest(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, SettingsSeeder::class, CommunicationTemplateSeeder::class]);
        Mail::fake();

        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Finance Manager'));

        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        $payment = Payment::factory()->paid()->create([
            'reservation_id' => $reservation->id,
            'amount' => 150,
        ]);

        $this->actingAs($user)
            ->withConfirmedPassword()
            ->post(route('admin.payments.refund', $payment), [
                'amount' => 150,
                'reason' => 'Guest cancelled',
            ])->assertRedirect();

        Mail::assertSent(GuestCommunicationMail::class, function (GuestCommunicationMail $mail) use ($reservation): bool {
            return $mail->hasTo($reservation->guest->email)
                && str_contains($mail->emailSubject, $reservation->reference)
                && str_contains($mail->emailBody, '£150.00')
                && str_contains($mail->emailBody, 'Reason: Guest cancelled');
        });

        $this->assertDatabaseHas('communications', [
            'reservation_id' => $reservation->id,
            'recipient' => $reservation->guest->email,
            'status' => 'sent',
        ]);
    }

    public function test_refund_email_can_be_disabled_by_setting(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, SettingsSeeder::class, CommunicationTemplateSeeder::class]);
        Mail::fake();

        Setting::query()->where('key', 'email_payment_refund_enabled')->firstOrFail()->update(['value' => '0']);

        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Finance Manager'));

        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        $payment = Payment::factory()->paid()->create([
            'reservation_id' => $reservation->id,
            'amount' => 150,
        ]);

        $this->actingAs($user)
            ->withConfirmedPassword()
            ->post(route('admin.payments.refund', $payment), [
                'amount' => 150,
            ])->assertRedirect();

        Mail::assertNotSent(GuestCommunicationMail::class);
        $this->assertDatabaseCount('communications', 0);
    }
}
