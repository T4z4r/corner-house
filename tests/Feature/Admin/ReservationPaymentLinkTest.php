<?php

namespace Tests\Feature\Admin;

use App\Mail\PaymentLinkMail;
use App\Models\Guest;
use App\Models\PaymentLink;
use App\Models\Reservation;
use App\Models\User;
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

        $reservation = Reservation::factory()->create(['guests_count' => 2]);
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
