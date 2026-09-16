<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Stripe\Account;
use Stripe\Balance;
use Stripe\Collection;
use Stripe\Payout;
use Stripe\Service\AccountService;
use Stripe\Service\BalanceService;
use Stripe\Service\PayoutService;
use Stripe\StripeClient;
use Tests\TestCase;

class InstantPayoutTest extends TestCase
{
    use RefreshDatabase;

    private function financeUser(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Finance Manager');
        $this->actingAs($user)->withConfirmedPassword();

        return $user;
    }

    private function stripe(int $available = 10000): MockInterface
    {
        $accounts = Mockery::mock(AccountService::class);
        $accounts->shouldReceive('retrieve')->andReturn(Account::constructFrom(['id' => 'acct_own', 'payouts_enabled' => true]));
        $accounts->shouldReceive('allExternalAccounts')->with('acct_own', ['limit' => 100])->andReturn(Collection::constructFrom([
            'object' => 'list', 'has_more' => false,
            'data' => [
                ['id' => 'card_eligible', 'object' => 'card', 'currency' => 'gbp', 'brand' => 'Visa', 'last4' => '1234', 'available_payout_methods' => ['standard', 'instant']],
                ['id' => 'card_ineligible', 'object' => 'card', 'currency' => 'gbp', 'brand' => 'Visa', 'last4' => '9999', 'available_payout_methods' => ['standard']],
            ],
        ]));
        $balances = Mockery::mock(BalanceService::class);
        $balances->shouldReceive('retrieve')->andReturn(Balance::constructFrom([
            'livemode' => false,
            'instant_available' => [['amount' => $available, 'currency' => 'gbp', 'source_types' => ['card' => $available]]],
        ]));
        $payouts = Mockery::mock(PayoutService::class);
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('accounts')->andReturn($accounts);
        $client->shouldReceive('getService')->with('balance')->andReturn($balances);
        $client->shouldReceive('getService')->with('payouts')->andReturn($payouts);
        $this->app->instance(StripeClient::class, $client);

        return $payouts;
    }

    public function test_review_and_confirmation_send_only_the_reviewed_amount_once(): void
    {
        $user = $this->financeUser();
        $payouts = $this->stripe();
        $payouts->shouldReceive('create')->once()->withArgs(function (array $payload, array $options) use ($user): bool {
            return $payload === [
                'amount' => 1250, 'currency' => 'gbp', 'destination' => 'card_eligible',
                'method' => 'instant', 'source_type' => 'card', 'metadata' => ['requested_by' => (string) $user->id],
            ] && $options['idempotency_key'] === session('instant_payout_review.key');
        })->andReturn(Payout::constructFrom(['id' => 'po_test', 'status' => 'pending']));

        $this->get(route('admin.payments.instant'))->assertOk()
            ->assertSee('Visa ending 1234')->assertDontSee('9999')->assertSee('100.00');
        $this->post(route('admin.payments.instant.review'), ['destination' => 'card_eligible', 'amount' => '12.50'])
            ->assertRedirect(route('admin.payments.instant'));
        $this->get(route('admin.payments.instant'))->assertOk()->assertSee('Review payout')->assertSee('12.50');
        $key = session('instant_payout_review.key');
        $this->post(route('admin.payments.instant.store'), ['review_key' => $key, 'confirmation' => 1, 'amount' => 9999, 'destination' => 'card_other'])
            ->assertSessionHas('status', 'Payout po_test submitted to Stripe. Status: pending.');
        $this->post(route('admin.payments.instant.store'), ['review_key' => $key, 'confirmation' => 1])
            ->assertSessionHas('status', 'Payout po_test has already been submitted.');
        $this->assertDatabaseHas('audit_logs', ['action' => 'payments.instant_payout', 'record_id' => 'po_test', 'user_id' => $user->id]);
    }

    public function test_ineligible_destinations_and_excessive_amounts_do_not_create_payouts(): void
    {
        $this->financeUser();
        $this->stripe()->shouldNotReceive('create');
        $this->post(route('admin.payments.instant.review'), ['destination' => 'card_ineligible', 'amount' => '10.00'])
            ->assertSessionHasErrors('payout');
        $this->post(route('admin.payments.instant.review'), ['destination' => 'card_other', 'amount' => '10.00'])
            ->assertSessionHasErrors('payout');
        $this->post(route('admin.payments.instant.review'), ['destination' => 'card_eligible', 'amount' => '100.01'])
            ->assertSessionHasErrors('payout');
        $this->post(route('admin.payments.instant.review'), ['destination' => 'card_eligible', 'amount' => '1.001'])
            ->assertSessionHasErrors('amount');
    }

    public function test_no_instant_balance_disables_payout_options(): void
    {
        $this->financeUser();
        $this->stripe(0)->shouldNotReceive('create');
        $this->get(route('admin.payments.instant'))->assertOk()->assertSee('No eligible destination');
    }

    public function test_confirmation_and_unexpired_review_are_required(): void
    {
        $this->financeUser();
        $this->stripe()->shouldNotReceive('create');
        $this->post(route('admin.payments.instant.review'), ['destination' => 'card_eligible', 'amount' => '10'])
            ->assertRedirect();
        $key = session('instant_payout_review.key');
        $this->post(route('admin.payments.instant.store'), ['review_key' => $key])->assertSessionHasErrors('confirmation');
        $this->travel(16)->minutes();
        $this->post(route('admin.payments.instant.store'), ['review_key' => $key, 'confirmation' => 1])->assertSessionHasErrors('payout');
    }

    public function test_retry_reuses_the_same_stripe_idempotency_key(): void
    {
        $this->financeUser();
        $payouts = $this->stripe();
        $keys = [];
        $payouts->shouldReceive('create')->twice()->andReturnUsing(function (array $payload, array $options) use (&$keys): Payout {
            $keys[] = $options['idempotency_key'];
            if (count($keys) === 1) {
                throw new \RuntimeException('Response lost');
            }

            return Payout::constructFrom(['id' => 'po_retry', 'status' => 'pending']);
        });
        $this->post(route('admin.payments.instant.review'), ['destination' => 'card_eligible', 'amount' => '10']);
        $data = ['review_key' => session('instant_payout_review.key'), 'confirmation' => 1];
        $this->post(route('admin.payments.instant.store'), $data)->assertSessionHasErrors('payout');
        $this->post(route('admin.payments.instant.store'), $data)->assertSessionHas('status');
        $this->assertSame($keys[0], $keys[1]);
    }

    public function test_users_without_permission_cannot_access_or_send_payouts(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('payments.view');
        $this->actingAs($user)->withConfirmedPassword();
        $this->get(route('admin.payments.instant'))->assertForbidden();
        $this->post(route('admin.payments.instant.review'), [])->assertForbidden();
        $this->post(route('admin.payments.instant.store'), [])->assertForbidden();
    }

    public function test_stripe_failure_shows_a_safe_message(): void
    {
        $this->financeUser();
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('accounts')->andThrow(new \RuntimeException('Private credentials error'));
        $this->app->instance(StripeClient::class, $client);
        $this->get(route('admin.payments.instant'))->assertOk()
            ->assertSee('Unable to load payout eligibility')->assertDontSee('Private credentials error');
    }
}
