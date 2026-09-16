<?php

namespace Tests\Feature;

use App\Models\Communication;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_counts_totals_and_pending_items(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Enquiry::factory()->create(['status' => 'new']);
        Enquiry::factory()->create(['status' => 'approved']);
        $reservation = Reservation::factory()->create(['status' => 'hold']);
        Payment::factory()->create(['reservation_id' => $reservation->id, 'status' => 'pending']);
        Communication::factory()->create(['status' => 'pending']);
        Communication::factory()->create(['status' => 'sent']);
        $this->actingAs($user)->get(route('admin.guests.index'))->assertOk()
            ->assertSee('aria-label="2 total enquiries"', false)
            ->assertSee('aria-label="1 pending enquiries"', false)
            ->assertSee('aria-label="1 pending bookings"', false)
            ->assertSee('aria-label="1 pending payments"', false)
            ->assertSee('aria-label="2 total messages"', false)
            ->assertSee('aria-label="4 total pending"', false);
        $reservation->update(['status' => 'confirmed']);
        $this->get(route('admin.guests.index'))->assertSee('aria-label="3 total pending"', false);
    }

    public function test_sidebar_counts_only_permitted_sections_and_shows_zero(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['guests.view', 'enquiries.view']);
        Payment::factory()->create(['status' => 'pending']);
        $this->actingAs($user)->get(route('admin.guests.index'))->assertOk()
            ->assertSee('aria-label="0 total enquiries"', false)
            ->assertSee('aria-label="0 total pending"', false)
            ->assertDontSee('total payments')->assertDontSee('total bookings')->assertDontSee('total messages');
    }
}
