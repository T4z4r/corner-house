<?php

namespace Tests\Feature\Admin;

use App\Models\Enquiry;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}