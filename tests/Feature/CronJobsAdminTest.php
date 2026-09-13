<?php

namespace Tests\Feature;

use App\Jobs\ExpireBookingHoldsJob;
use App\Models\CronJobRun;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CronJobsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_requires_authentication(): void
    {
        $this->get(route('admin.cron-jobs'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_cron_jobs(): void
    {
        $role = Role::findByName('Support Staff');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.cron-jobs'))
            ->assertForbidden();
    }

    public function test_user_with_settings_permission_can_view_cron_jobs(): void
    {
        $role = Role::create(['name' => 'Settings Admin', 'guard_name' => 'web']);
        $role->givePermissionTo('settings.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.cron-jobs'))
            ->assertOk()
            ->assertSee('Cron Jobs')
            ->assertSee('Expire Booking Holds')
            ->assertSee('Push Beds24 Rates');
    }

    public function test_page_lists_runs_with_status_and_summary(): void
    {
        CronJobRun::create([
            'job' => 'SyncBeds24BookingsJob',
            'status' => 'success',
            'started_at' => now()->subHour(),
            'finished_at' => now(),
            'duration_ms' => 1200,
        ]);

        CronJobRun::create([
            'job' => 'SyncBeds24BookingsJob',
            'status' => 'failed',
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHours(2),
            'duration_ms' => 300,
            'error' => 'boom: connection refused',
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.cron-jobs'))
            ->assertOk()
            ->assertSee('Beds24 Bookings Sync', false)
            ->assertSee('<span class="ch-badge ch-badge-success">Success</span>', false)
            ->assertSee('<span class="ch-badge ch-badge-danger">Failed</span>', false)
            ->assertSee('boom: connection refused')
            ->assertSee('1', false);
    }

    public function test_super_admin_can_trigger_a_cron_job(): void
    {
        Queue::fake();

        $this->actingAs($this->superAdmin())
            ->from(route('admin.cron-jobs'))
            ->post(route('admin.cron-jobs.run', 'ExpireBookingHoldsJob'))
            ->assertRedirect(route('admin.cron-jobs'))
            ->assertSessionHas('status');

        Queue::assertPushed(ExpireBookingHoldsJob::class);
    }

    public function test_triggering_unknown_job_returns_404(): void
    {
        Queue::fake();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.cron-jobs.run', 'NopeJob'))
            ->assertNotFound();

        Queue::assertNothingPushed();
    }

    public function test_user_without_permission_cannot_trigger_a_cron_job(): void
    {
        $role = Role::findByName('Support Staff');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->post(route('admin.cron-jobs.run', 'ExpireBookingHoldsJob'))
            ->assertForbidden();
    }

    public function test_status_filter_filters_run_history(): void
    {
        CronJobRun::create([
            'job' => 'GenerateRevenueSnapshotJob',
            'status' => 'failed',
            'started_at' => now()->subHour(),
            'finished_at' => now(),
            'duration_ms' => 500,
            'error' => 'snapshot boom',
        ]);

        CronJobRun::create([
            'job' => 'SyncBeds24MessagesJob',
            'status' => 'success',
            'started_at' => now()->subHours(3),
            'finished_at' => now()->subHours(3),
            'duration_ms' => 900,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.cron-jobs', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('snapshot boom');

        $this->actingAs($this->superAdmin())
            ->get(route('admin.cron-jobs', ['status' => 'success']))
            ->assertOk()
            ->assertDontSee('snapshot boom');
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }
}
