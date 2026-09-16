<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentGateway;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Balance;
use Stripe\Service\BalanceService;
use Stripe\StripeClient;
use Tests\TestCase;

class PaymentBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsFinanceManager(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Finance Manager');
        $this->actingAs($user)->withConfirmedPassword();
    }

    public function test_finance_manager_sees_live_available_and_pending_balances_by_currency(): void
    {
        $this->actingAsFinanceManager();
        $this->mock(PaymentGatewayInterface::class)->shouldReceive('retrieveBalance')->once()->andReturn([
            'livemode' => true,
            'available' => [
                ['amount' => 123456, 'currency' => 'gbp'],
                ['amount' => 5000, 'currency' => 'jpy'],
                ['amount' => 1000, 'currency' => 'isk'],
            ],
            'pending' => [['amount' => -250, 'currency' => 'usd']],
        ]);

        $this->get(route('admin.payments.index'))->assertOk()
            ->assertSee('Available balance')->assertSee('Pending balance')
            ->assertSee('Live mode')->assertDontSee('Test mode')
            ->assertSee('GBP')->assertSee('1,234.56')
            ->assertSee('JPY')->assertSee('5,000')
            ->assertSee('ISK')->assertSee('10.00')
            ->assertSee('USD')->assertSee('-2.50')
            ->assertSee('Transaction history');
    }

    public function test_empty_test_mode_balances_are_labelled(): void
    {
        $this->actingAsFinanceManager();

        $this->get(route('admin.payments.index'))->assertOk()
            ->assertSee('Test mode')
            ->assertSee('No available balances reported by Stripe.')
            ->assertSee('No pending balances reported by Stripe.');
    }

    public function test_stripe_failure_does_not_hide_payment_history_or_expose_error_details(): void
    {
        $this->actingAsFinanceManager();
        $this->mock(PaymentGatewayInterface::class)->shouldReceive('retrieveBalance')->once()
            ->andThrow(new \RuntimeException('Sensitive provider error'));

        $this->get(route('admin.payments.index'))->assertOk()
            ->assertSee('Stripe balance is currently unavailable.')
            ->assertSee('Transaction history')
            ->assertDontSee('Sensitive provider error');
    }

    public function test_user_without_payment_permission_cannot_read_balances(): void
    {
        $this->actingAs(User::factory()->create())->withConfirmedPassword();
        $this->mock(PaymentGatewayInterface::class)->shouldNotReceive('retrieveBalance');

        $this->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_stripe_gateway_retrieves_account_balance_without_exposing_extra_fields(): void
    {
        $service = Mockery::mock(BalanceService::class);
        $service->shouldReceive('retrieve')->once()->andReturn(Balance::constructFrom([
            'livemode' => true,
            'available' => [['amount' => 12345, 'currency' => 'gbp', 'source_types' => ['card' => 12345]]],
            'pending' => [['amount' => 500, 'currency' => 'eur']],
        ]));
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('balance')->once()->andReturn($service);

        $balance = (new StripePaymentGateway($client))->retrieveBalance();

        $this->assertSame([
            'livemode' => true,
            'available' => [['amount' => 12345, 'currency' => 'gbp']],
            'pending' => [['amount' => 500, 'currency' => 'eur']],
        ], $balance);
    }
}
