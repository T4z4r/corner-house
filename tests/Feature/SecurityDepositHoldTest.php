<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentService;
use App\Services\Payment\SecurityDepositService;
use App\Services\Payment\StripePaymentGateway;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Mockery;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;
use Tests\TestCase;

class SecurityDepositHoldTest extends TestCase
{
    use RefreshDatabase;

    private function reservation(): Reservation
    {
        return Reservation::factory()->create([
            'check_in' => today()->addDay(), 'check_out' => today()->addDays(4),
            'security_deposit_amount' => 950, 'total_amount' => 1200, 'paid_amount' => 1200,
        ]);
    }

    private function financeUser(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Finance Manager');

        return $user;
    }

    public function test_finance_can_request_a_separate_hold_and_repeated_requests_reuse_it(): void
    {
        $this->actingAs($this->financeUser())->withConfirmedPassword();
        $reservation = $this->reservation();

        $this->post(route('admin.reservations.security-deposit', $reservation))->assertRedirect();
        $payment = $reservation->payments()->sole();
        $this->post(route('admin.reservations.security-deposit', $reservation))->assertRedirect(route('admin.payments.show', $payment));
        $this->get(route('admin.payments.show', $payment))->assertSee('Guest hold link');
        $this->assertSame('950.00', $payment->amount);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('1200.00', $reservation->fresh()->paid_amount);
        $this->assertSame(1, $reservation->payments()->count());
    }

    public function test_guest_authorisation_records_a_hold_without_changing_booking_finances(): void
    {
        $reservation = $this->reservation();
        $before = $reservation->fresh()->getAttributes();
        $service = app(SecurityDepositService::class);
        $payment = $service->requestHold($reservation);
        $url = $service->guestUrl($payment);

        $this->get($url)->assertSee('Authorise security deposit hold');
        $intentId = $payment->fresh()->provider_payment_id;
        $gateway = app(PaymentGatewayInterface::class);
        $this->assertSame('manual', $gateway->intents[$intentId]['capture_method']);
        $this->get($url.'&payment_intent=pi_untrusted&redirect_status=succeeded')->assertSee('Security deposit authorised');
        $this->assertSame('processing', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertSame($before, $reservation->fresh()->getAttributes());
    }

    public function test_hold_links_require_an_unexpired_signature(): void
    {
        $payment = app(SecurityDepositService::class)->requestHold($this->reservation());

        $this->get(route('booking.security-deposit', $payment))->assertForbidden();
        $this->get(URL::temporarySignedRoute('booking.security-deposit', now()->subMinute(), ['payment' => $payment]))->assertForbidden();
        $this->assertNull($payment->fresh()->provider_payment_id);
    }

    public function test_requesting_a_hold_too_early_does_not_create_a_payment(): void
    {
        $this->actingAs($this->financeUser())->withConfirmedPassword();
        $reservation = $this->reservation();
        $reservation->update(['check_in' => today()->addDays(20), 'check_out' => today()->addDays(23)]);

        $this->post(route('admin.reservations.security-deposit', $reservation))->assertSessionHasErrors('error');
        $this->assertSame(0, $reservation->payments()->count());
    }

    public function test_legacy_deposits_cannot_be_held_again(): void
    {
        $this->actingAs($this->financeUser())->withConfirmedPassword();
        $reservation = $this->reservation();
        $reservation->update(['security_deposit_amount' => null]);

        $this->post(route('admin.reservations.security-deposit', $reservation))->assertSessionHasErrors('error');
        $this->assertSame(0, $reservation->payments()->count());
    }

    public function test_hold_release_does_not_create_a_refund_or_change_the_booking_balance(): void
    {
        $this->actingAs($this->financeUser())->withConfirmedPassword();
        $reservation = $this->reservation();
        $service = app(SecurityDepositService::class);
        $payment = $service->prepare($service->requestHold($reservation));
        $service->sync($payment);

        $this->post(route('admin.payments.release-hold', $payment))->assertSessionHas('status');
        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertSame('1200.00', $reservation->fresh()->paid_amount);
        $this->assertSame('paid', $reservation->fresh()->payment_status);
        $this->assertDatabaseCount('refunds', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payments.hold_released', 'record_id' => (string) $payment->id]);
    }

    public function test_hold_release_requires_refund_permission(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('payments.view');
        $this->actingAs($user)->withConfirmedPassword();
        $reservation = $this->reservation();
        $payment = app(SecurityDepositService::class)->requestHold($reservation);

        $this->post(route('admin.payments.release-hold', $payment))->assertForbidden();
        $this->post(route('admin.reservations.security-deposit', $reservation))->assertForbidden();
    }

    public function test_webhook_reconciles_expired_holds_without_counting_them_as_paid(): void
    {
        $reservation = $this->reservation();
        $service = app(SecurityDepositService::class);
        $payment = $service->prepare($service->requestHold($reservation));
        $payments = app(PaymentService::class);
        $payments->handleWebhook(json_encode(['type' => 'payment_intent.amount_capturable_updated', 'data' => ['object' => ['id' => $payment->provider_payment_id]]]), 'fake');
        $this->assertSame('processing', $payment->fresh()->status);

        app(PaymentGatewayInterface::class)->releaseHold($payment->provider_payment_id);
        $payments->handleWebhook(json_encode(['type' => 'payment_intent.canceled', 'data' => ['object' => ['id' => $payment->provider_payment_id]]]), 'fake');
        $this->assertSame('cancelled', $payment->fresh()->status);
        $payments->handleWebhook(json_encode(['type' => 'payment_intent.amount_capturable_updated', 'data' => ['object' => ['id' => $payment->provider_payment_id]]]), 'fake');
        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertSame('1200.00', $reservation->fresh()->paid_amount);
    }

    public function test_new_booking_checkout_excludes_the_separate_deposit_even_if_deposit_option_is_requested(): void
    {
        $reservation = $this->reservation();
        $reservation->update(['paid_amount' => 0, 'payment_status' => 'unpaid']);

        $this->get(route('booking.checkout', [$reservation, 'payment_option' => 'deposit']))
            ->assertViewHas('paymentAmount', 1200.0)
            ->assertViewHas('paymentOption', 'full')
            ->assertSee('separate card hold near arrival')
            ->assertDontSee('Choose how much to pay');
        $this->assertSame('1200.00', $reservation->payments()->sole()->amount);
    }

    public function test_booking_with_a_hold_cannot_be_deleted(): void
    {
        $reservation = $this->reservation();
        $reservation->update(['paid_amount' => 0, 'payment_status' => 'unpaid']);
        app(SecurityDepositService::class)->requestHold($reservation);
        $this->expectException(\DomainException::class);

        app(BookingService::class)->delete($reservation);
    }

    public function test_gateway_uses_manual_card_authorisation_and_returns_the_expiry(): void
    {
        $intents = Mockery::mock(PaymentIntentService::class);
        $intents->shouldReceive('create')->once()->with(Mockery::on(fn (array $params): bool => $params['capture_method'] === 'manual' && $params['payment_method_types'] === ['card'] && $params['amount'] === 95000 && ! isset($params['automatic_payment_methods'])), ['idempotency_key' => 'deposit-1'])
            ->andReturn(PaymentIntent::constructFrom(['id' => 'pi_hold', 'client_secret' => 'secret', 'status' => 'requires_payment_method']));
        $intents->shouldReceive('retrieve')->with('pi_hold', ['expand' => ['latest_charge']])->once()
            ->andReturn(PaymentIntent::constructFrom(['id' => 'pi_hold', 'status' => 'requires_capture', 'amount' => 95000, 'currency' => 'gbp', 'latest_charge' => ['payment_method_details' => ['card' => ['capture_before' => 1800000000]]]]));
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($intents);
        $gateway = new StripePaymentGateway($client);

        $gateway->createPaymentIntent(['amount' => 950, 'currency' => 'GBP', 'capture_method' => 'manual', 'idempotency_key' => 'deposit-1']);
        $this->assertSame(1800000000, $gateway->retrievePaymentIntent('pi_hold')['capture_before']);
    }

    public function test_gateway_releases_authorised_funds_by_cancelling_instead_of_refunding(): void
    {
        $intents = Mockery::mock(PaymentIntentService::class);
        $intents->shouldReceive('retrieve')->with('pi_hold')->once()->andReturn(PaymentIntent::constructFrom(['status' => 'requires_capture']));
        $intents->shouldReceive('cancel')->with('pi_hold', [], ['idempotency_key' => 'release-pi_hold'])->once();
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($intents);

        (new StripePaymentGateway($client))->releaseHold('pi_hold');
    }

    public function test_captured_funds_cannot_be_released_as_a_hold(): void
    {
        $intents = Mockery::mock(PaymentIntentService::class);
        $intents->shouldReceive('retrieve')->with('pi_paid')->once()->andReturn(PaymentIntent::constructFrom(['status' => 'succeeded']));
        $intents->shouldNotReceive('cancel');
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($intents);
        $this->expectException(\DomainException::class);

        (new StripePaymentGateway($client))->releaseHold('pi_paid');
    }
}
