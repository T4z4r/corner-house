<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentGateway;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Service\Checkout\SessionService;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;
use Tests\TestCase;

class DeletePendingPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Finance Manager');
        $this->actingAs($user)->withConfirmedPassword();
    }

    public function test_pending_payment_is_cancelled_deleted_and_audited_without_changing_booking(): void
    {
        $payment = Payment::factory()->create(['status' => 'pending', 'paid_at' => null, 'provider' => 'stripe', 'provider_session_id' => 'cs_pending', 'provider_payment_id' => 'pi_pending']);
        $reservation = $payment->reservation;
        $before = $reservation->getAttributes();
        $this->mock(PaymentGatewayInterface::class)->shouldReceive('cancelPendingPayment')->once()->with('cs_pending', 'pi_pending');

        $this->get(route('admin.payments.show', $payment))->assertOk()->assertSee('Delete pending payment');
        $this->delete(route('admin.payments.destroy', $payment))->assertRedirect(route('admin.payments.index'))->assertSessionHas('status');

        $this->assertModelMissing($payment);
        $this->assertSame($before, $reservation->fresh()->getAttributes());
        $this->assertDatabaseHas('audit_logs', ['action' => 'payments.deleted', 'record_id' => (string) $payment->id]);
    }

    public function test_completed_payment_cannot_be_deleted(): void
    {
        $payment = Payment::factory()->paid()->create();
        $this->mock(PaymentGatewayInterface::class)->shouldNotReceive('cancelPendingPayment');

        $this->get(route('admin.payments.show', $payment))->assertOk()->assertDontSee('Delete pending payment');
        $this->delete(route('admin.payments.destroy', $payment))->assertSessionHasErrors('error');

        $this->assertModelExists($payment);
    }

    public function test_stripe_failure_keeps_the_pending_payment(): void
    {
        $payment = Payment::factory()->create(['status' => 'pending', 'paid_at' => null, 'provider' => 'stripe']);
        $this->mock(PaymentGatewayInterface::class)->shouldReceive('cancelPendingPayment')->once()->andThrow(new \RuntimeException('Connection failed'));

        $this->delete(route('admin.payments.destroy', $payment))->assertSessionHasErrors('error');

        $this->assertModelExists($payment);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'payments.deleted']);
    }

    public function test_payment_already_paid_at_stripe_is_not_deleted(): void
    {
        $payment = Payment::factory()->create(['status' => 'pending', 'paid_at' => null, 'provider' => 'stripe']);
        $this->mock(PaymentGatewayInterface::class)->shouldReceive('cancelPendingPayment')->once()->andThrow(new \DomainException('Stripe has already completed this checkout.'));

        $this->delete(route('admin.payments.destroy', $payment))->assertSessionHasErrors('error');

        $this->assertModelExists($payment);
    }

    public function test_payment_view_permission_does_not_allow_deleting(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('payments.view');
        $this->actingAs($user);
        $payment = Payment::factory()->create(['status' => 'pending', 'paid_at' => null]);

        $this->delete(route('admin.payments.destroy', $payment))->assertForbidden();

        $this->assertModelExists($payment);
    }

    public function test_stripe_gateway_expires_checkout_and_cancels_the_separate_card_intent(): void
    {
        $sessions = Mockery::mock(SessionService::class);
        $sessions->shouldReceive('retrieve')->with('cs_pending')->once()->andReturn(Session::constructFrom(['status' => 'open', 'payment_status' => 'unpaid', 'payment_intent' => null]));
        $sessions->shouldReceive('expire')->with('cs_pending')->once();
        $checkout = (object) ['sessions' => $sessions];
        $intents = Mockery::mock(PaymentIntentService::class);
        $intents->shouldReceive('retrieve')->with('pi_pending')->once()->andReturn(PaymentIntent::constructFrom(['status' => 'requires_payment_method']));
        $intents->shouldReceive('cancel')->with('pi_pending')->once();
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('checkout')->andReturn($checkout);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($intents);

        (new StripePaymentGateway($client))->cancelPendingPayment('cs_pending', 'pi_pending');
    }

    public function test_stripe_gateway_refuses_to_cancel_a_processing_intent(): void
    {
        $intents = Mockery::mock(PaymentIntentService::class);
        $intents->shouldReceive('retrieve')->with('pi_processing')->once()->andReturn(PaymentIntent::constructFrom(['status' => 'processing']));
        $intents->shouldNotReceive('cancel');
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($intents);
        $this->expectException(\DomainException::class);

        (new StripePaymentGateway($client))->cancelPendingPayment(null, 'pi_processing');
    }
}
