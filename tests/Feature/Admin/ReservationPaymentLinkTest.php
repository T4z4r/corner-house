<?php

namespace Tests\Feature\Admin;

use App\Mail\PaymentLinkMail;
use App\Models\Guest;
use App\Models\PaymentLink;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payment\PaymentLinkService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReservationPaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_email_a_payment_link_for_a_reservation(): void
    {
        Mail::fake();

        $reservation = Reservation::factory()->create(['guests_count' => 2, 'payment_status' => 'unpaid', 'paid_amount' => 0]);
        $user = $this->actingSuperAdmin();

        $this->post(route('admin.reservations.payment-link', $reservation))
            ->assertRedirect()
            ->assertSessionHas('status');

        $paymentLink = PaymentLink::query()->where('reservation_id', $reservation->id)->latest()->first();
        $this->assertNotNull($paymentLink);
        $this->assertTrue($paymentLink->expires_at->gt(now()->addHours(23)));
        $this->assertTrue($paymentLink->expires_at->lt(now()->addHours(25)));
        $this->assertSame($user->id, $paymentLink->created_by);

        Mail::assertSent(PaymentLinkMail::class, function (PaymentLinkMail $mail) use ($reservation, $paymentLink): bool {
            return $mail->hasTo($reservation->guest->email)
                && $mail->reservation->is($reservation)
                && $mail->paymentLink->is($paymentLink);
        });
    }

    public function test_payment_link_is_not_sent_when_the_booking_has_no_guest_email(): void
    {
        Mail::fake();

        $reservation = Reservation::factory()->create(['guest_id' => Guest::factory()->create(['email' => null])]);

        $this->actingAs($this->superAdmin())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.reservations.payment-link', $reservation))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseCount('payment_links', 0);
        Mail::assertNothingSent();
    }

    public function test_user_without_communications_permission_cannot_send_a_payment_link(): void
    {
        Mail::fake();

        $role = Role::create(['name' => 'No Communications', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view', 'reservations.view');
        $user = User::factory()->create()->assignRole($role);

        $reservation = Reservation::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.reservations.payment-link', $reservation))
            ->assertForbidden();

        $this->assertDatabaseCount('payment_links', 0);
        Mail::assertNothingSent();
    }

    public function test_admin_can_generate_and_email_another_link_for_a_partial_booking(): void
    {
        Mail::fake();
        $reservation = Reservation::factory()->create(['total_amount' => 1500, 'paid_amount' => 950, 'payment_status' => 'partial']);
        $oldLink = app(PaymentLinkService::class)->createForReservation($reservation);
        $this->actingSuperAdmin();

        $this->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Generate and email balance payment link');
        $this->post(route('admin.reservations.payment-link', $reservation))
            ->assertRedirect()
            ->assertSessionHas('status');

        $newLink = $reservation->paymentLinks()->latest('id')->firstOrFail();
        $this->assertNotSame($oldLink->token, $newLink->token);
        $this->assertTrue(app(PaymentLinkService::class)->activeFor($reservation)->is($newLink));
        Mail::assertSent(PaymentLinkMail::class, fn (PaymentLinkMail $mail): bool => $mail->paymentLink->is($newLink) && $mail->hasTo($reservation->guest->email));
    }

    public function test_balance_payment_email_shows_the_outstanding_amount_in_html_and_text(): void
    {
        $reservation = Reservation::factory()->create(['total_amount' => 1500, 'paid_amount' => 950, 'payment_status' => 'partial']);
        $link = app(PaymentLinkService::class)->createForReservation($reservation);

        $mail = new PaymentLinkMail($reservation, $link);

        $mail->assertSeeInHtml('remaining balance of &pound;550.00', false);
        $mail->assertSeeInHtml('Proceed to Pay');
        $mail->assertDontSeeInHtml('Pay Refundable Deposit');
        $mail->assertSeeInHtml('Please check your junk or spam folder');
        $mail->assertSeeInText('remaining balance of £550.00');
    }

    public function test_payment_link_text_email_reminds_guests_to_check_junk_or_spam(): void
    {
        $reservation = Reservation::factory()->create(['total_amount' => 1500, 'paid_amount' => 950, 'payment_status' => 'partial']);
        $link = app(PaymentLinkService::class)->createForReservation($reservation);

        $mail = new PaymentLinkMail($reservation, $link);

        $mail->assertSeeInText('Please check your junk or spam folder');
    }

    public function test_a_fully_paid_booking_cannot_receive_another_payment_request(): void
    {
        Mail::fake();
        $reservation = Reservation::factory()->create();
        $this->actingSuperAdmin();

        $this->post(route('admin.reservations.payment-link', $reservation))
            ->assertSessionHasErrors(['error' => 'This booking has no outstanding balance.']);

        $this->assertDatabaseCount('payment_links', 0);
        Mail::assertNothingSent();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    private function actingSuperAdmin(): User
    {
        $user = $this->superAdmin();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

        return $user;
    }
}
