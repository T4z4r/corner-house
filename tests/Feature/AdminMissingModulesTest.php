<?php

namespace Tests\Feature;

use App\Jobs\FetchBeds24BookingsJob;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMissingModulesTest extends TestCase
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

    public function test_super_admin_can_open_new_admin_modules(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.channels.index'))->assertRedirect(route('admin.channels.integrations'));
        $this->actingAs($user)->get(route('admin.channels.integrations'))->assertOk();
        $this->actingAs($user)->get(route('admin.channels.setup.page'))->assertOk();
        $this->actingAs($user)->get(route('admin.communications.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.chatbot.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.revenue.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();
    }

    public function test_support_staff_cannot_manage_users_or_export_reports(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Support Staff'));

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.reports.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_user(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.users.store'), [
                'name' => 'Pat Manager',
                'email' => 'pat@example.com',
                'password' => 'password123',
                'role' => 'Property Manager',
            ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'pat@example.com']);
        $this->assertTrue(User::query()->where('email', 'pat@example.com')->first()->hasRole('Property Manager'));
    }

    public function test_dashboard_shows_revenue_from_reservations(): void
    {
        Reservation::factory()->create([
            'status' => 'confirmed',
            'total_amount' => 250,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('250.00');
    }

    public function test_reports_export_downloads_csv(): void
    {
        Reservation::factory()->create([
            'status' => 'confirmed',
            'reference' => 'CH-EXPORT',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.export', ['type' => 'revenue']));

        $response->assertOk();
        $this->assertStringContainsString('CH-EXPORT', $response->streamedContent());
    }

    public function test_communications_template_can_be_saved(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.communications.templates.store'), [
                'name' => 'Welcome',
                'event' => 'booking_confirmation',
                'channel' => 'email',
                'subject' => 'Hello',
                'body' => 'Welcome {{guest_name}}',
                'is_active' => true,
            ])->assertRedirect();

        $this->assertDatabaseHas('communication_templates', ['name' => 'Welcome']);
    }

    public function test_platform_links_save_and_become_visible_on_the_booking_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->put(route('admin.website.platforms.update'), [
                'platform_airbnb_url' => 'https://www.airbnb.co.uk/rooms/123456',
                'platform_booking_url' => 'https://www.booking.com/hotel/gb/corner-house',
                'platform_vrbo_url' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('https://www.airbnb.co.uk/rooms/123456', Setting::getValue('platform_airbnb_url'));
        $this->assertSame('https://www.booking.com/hotel/gb/corner-house', Setting::getValue('platform_booking_url'));
        $this->assertNull(Setting::getValue('platform_vrbo_url'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'platforms.updated']);

        $this->actingAs($this->superAdmin())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('https://www.airbnb.co.uk/rooms/123456', false)
            ->assertSee('https://www.booking.com/hotel/gb/corner-house', false);
    }

    public function test_platform_links_save_even_when_one_url_is_invalid(): void
    {
        $this->actingAs($this->superAdmin())
            ->put(route('admin.website.platforms.update'), [
                'platform_airbnb_url' => 'not a valid url',
                'platform_booking_url' => 'https://www.booking.com/hotel/gb/corner-house',
                'platform_vrbo_url' => '',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('platform_airbnb_url');

        $this->assertNull(Setting::getValue('platform_airbnb_url'));
        $this->assertSame('https://www.booking.com/hotel/gb/corner-house', Setting::getValue('platform_booking_url'));
    }

    public function test_bookings_page_offers_fetch_from_beds24(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee('Fetch from Beds24');
    }

    public function test_super_admin_can_fetch_beds24_bookings_immediately(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.reservations.fetch-beds24'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Beds24 bookings fetched. New and changed bookings have been imported.');

        $this->assertDatabaseHas('audit_logs', ['action' => 'channels.fetch_bookings']);
    }

    public function test_fetch_from_beds24_requires_channel_sync_permission(): void
    {
        Queue::fake([FetchBeds24BookingsJob::class]);

        $user = User::factory()->create();
        $user->givePermissionTo('reservations.view');

        $this->actingAs($user)
            ->post(route('admin.reservations.fetch-beds24'))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }
}
