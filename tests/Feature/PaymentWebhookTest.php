<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->post(route('admin.payments.refund', $payment), [
                'amount' => 150,
                'reason' => 'Guest cancelled',
            ])->assertRedirect();

        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
    }
}
