<?php

namespace Tests\Feature\Admin;

use App\Mail\BookingApprovalMail;
use App\Mail\BookingDeclinedMail;
use App\Models\Enquiry;
use App\Models\PaymentLink;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\Booking\BookingHoldService;
use App\Services\Booking\BookingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    public function test_super_admin_can_list_enquiries(): void
    {
        Enquiry::factory()->contact()->create(['name' => 'Sam Guest']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.enquiries.index'))
            ->assertOk()
            ->assertSee('Sam Guest');
    }

    public function test_user_without_enquiries_permission_cannot_list(): void
    {
        $role = Role::create(['name' => 'No Enquiries', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view');
        $user = User::factory()->create()->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.enquiries.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_mark_enquiry_as_read(): void
    {
        $enquiry = Enquiry::factory()->create(['status' => Enquiry::STATUS_NEW]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.enquiries.read', $enquiry))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'status' => Enquiry::STATUS_READ]);
    }

    public function test_marking_already_read_enquiry_is_idempotent(): void
    {
        $enquiry = Enquiry::factory()->read()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.enquiries.read', $enquiry))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'status' => Enquiry::STATUS_READ]);
    }

    public function test_user_without_enquiries_update_cannot_mark_read(): void
    {
        $role = Role::create(['name' => 'Read Only Enquiries', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'enquiries.view']);
        $user = User::factory()->create()->assignRole($role);
        $enquiry = Enquiry::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.enquiries.read', $enquiry))
            ->assertForbidden();
    }

    public function test_super_admin_can_delete_enquiry(): void
    {
        $enquiry = Enquiry::factory()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.enquiries.destroy', $enquiry))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('enquiries', ['id' => $enquiry->id]);
    }

    public function test_user_without_enquiries_delete_cannot_delete(): void
    {
        $role = Role::create(['name' => 'No Delete Enquiries', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'enquiries.view', 'enquiries.update']);
        $user = User::factory()->create()->assignRole($role);
        $enquiry = Enquiry::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.enquiries.destroy', $enquiry))
            ->assertForbidden();
    }

    private function bookingEnquiryWithHold(): array
    {
        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active']);
        $checkIn = Carbon::parse(now()->addDays(14)->toDateString());
        $checkOut = Carbon::parse(now()->addDays(16)->toDateString());

        $hold = app(BookingHoldService::class)->createHold($room->id, $checkIn, $checkOut, 'sess-enquiry', 160, 48 * 60)['hold'];

        $enquiry = Enquiry::factory()->create([
            'room_id' => $room->id,
            'booking_hold_id' => $hold->id,
            'name' => 'Alex Guest',
            'email' => 'alex@example.com',
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => 2,
        ]);

        return [$enquiry, $hold];
    }

    public function test_super_admin_can_approve_a_booking_request_and_email_a_24_hour_payment_link(): void
    {
        Mail::fake();

        [$enquiry, $hold] = $this->bookingEnquiryWithHold();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.enquiries.approve', $enquiry))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'status' => Enquiry::STATUS_APPROVED]);

        $reservation = Reservation::query()->first();
        $this->assertNotNull($reservation);
        $this->assertSame($enquiry->id, $reservation->enquiry->id);
        $this->assertSame('hold', $reservation->status);
        $this->assertSame($hold->id, $reservation->booking_hold_id);
        $this->assertSame('converted', $hold->fresh()->status);

        $paymentLink = PaymentLink::query()->where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($paymentLink);
        $this->assertTrue($paymentLink->expires_at->gt(now()->addHours(23)));
        $this->assertTrue($paymentLink->expires_at->lt(now()->addHours(25)));

        Mail::assertSent(BookingApprovalMail::class, function (BookingApprovalMail $mail) use ($enquiry, $paymentLink): bool {
            return $mail->hasTo('alex@example.com')
                && $mail->paymentLink->is($paymentLink)
                && $mail->enquiry->id === $enquiry->id;
        });
    }

    public function test_user_without_enquiries_update_cannot_approve(): void
    {
        $role = Role::create(['name' => 'No Approve Enquiries', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'enquiries.view']);
        $user = User::factory()->create()->assignRole($role);
        [$enquiry] = $this->bookingEnquiryWithHold();

        $this->actingAs($user)
            ->post(route('admin.enquiries.approve', $enquiry))
            ->assertForbidden();

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_approve_is_refused_when_the_room_is_no_longer_available(): void
    {
        Mail::fake();

        [$enquiry] = $this->bookingEnquiryWithHold();

        app(BookingService::class)->create([
            'room_id' => $enquiry->room_id,
            'check_in' => $enquiry->check_in->toDateString(),
            'check_out' => $enquiry->check_out->toDateString(),
            'guests_count' => 1,
            'status' => 'confirmed',
            'skip_availability' => true,
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.enquiries.approve', $enquiry))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertSame(Enquiry::STATUS_NEW, $enquiry->fresh()->status);
        $this->assertSame(1, Reservation::query()->count());
        Mail::assertNotSent(BookingApprovalMail::class);
        Mail::assertNotSent(BookingDeclinedMail::class);
    }

    public function test_super_admin_can_decline_a_booking_request_and_release_the_hold(): void
    {
        Mail::fake();

        [$enquiry, $hold] = $this->bookingEnquiryWithHold();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.enquiries.decline', $enquiry))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'status' => Enquiry::STATUS_DECLINED]);
        $this->assertSame('released', $hold->fresh()->status);
        $this->assertDatabaseCount('reservations', 0);

        Mail::assertSent(BookingDeclinedMail::class, fn (BookingDeclinedMail $mail): bool => $mail->hasTo($enquiry->email));
    }

    public function test_contact_enquiries_cannot_be_approved_or_declined(): void
    {
        $enquiry = Enquiry::factory()->contact()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.enquiries.approve', $enquiry))
            ->assertSessionHasErrors('error');

        $this->actingAs($this->superAdmin())
            ->post(route('admin.enquiries.decline', $enquiry))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseCount('reservations', 0);
    }
}
