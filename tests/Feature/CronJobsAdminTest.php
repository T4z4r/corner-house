<?php

namespace Tests\Feature;

use App\Jobs\ExpireBookingHoldsJob;
use App\Models\CronJobRun;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
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
        $this->withConfirmedPassword();
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
            ->assertDontSee('Push Beds24 Rates');
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

    public function test_admin_can_process_a_bounded_queue_batch(): void
    {
        Process::fake();
        config(['queue.default' => 'database']);
        $user = $this->superAdmin();
        $this->actingAs($user)->get(route('admin.cron-jobs'))->assertSee('Process queued jobs');

        $this->from(route('admin.cron-jobs'))->post(route('admin.cron-jobs.process-queue'))
            ->assertRedirect(route('admin.cron-jobs'))->assertSessionHas('status');

        Process::assertRan(fn (PendingProcess $process): bool => $process->path === base_path()
            && $process->timeout === 25
            && array_slice($process->command, 1) === [
                base_path('artisan'), 'queue:work', 'database', '--stop-when-empty', '--tries=3',
                '--max-time=15', '--max-jobs=25', '--timeout=15', '--sleep=1', '--no-interaction', '--no-ansi',
            ]);
        $lock = Cache::lock('admin-process-queue', 60);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_queue_processing_requires_write_permission_and_authentication(): void
    {
        Process::fake();
        $this->post(route('admin.cron-jobs.process-queue'))->assertRedirect(route('login'));
        $role = Role::create(['name' => 'Settings Viewer', 'guard_name' => 'web']);
        $role->givePermissionTo('settings.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->post(route('admin.cron-jobs.process-queue'))->assertForbidden();
        $this->get(route('admin.cron-jobs'))->assertDontSee('Process queued jobs');
        Process::assertNothingRan();
    }

    public function test_queue_processing_prevents_overlapping_manual_batches(): void
    {
        Process::fake();
        config(['queue.default' => 'database']);
        $lock = Cache::lock('admin-process-queue', 60);
        $lock->get();

        try {
            $this->actingAs($this->superAdmin())->post(route('admin.cron-jobs.process-queue'))
                ->assertSessionHasErrors('queue');
            Process::assertNothingRan();
        } finally {
            $lock->release();
        }
    }

    public function test_queue_worker_errors_are_reported_without_exposing_process_output(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'private connection details', exitCode: 1)]);
        config(['queue.default' => 'database']);

        $this->actingAs($this->superAdmin())->post(route('admin.cron-jobs.process-queue'))
            ->assertSessionHasErrors('queue')->assertSessionMissing('status');
        $this->assertStringNotContainsString('private connection details', session('errors')->first('queue'));
        $lock = Cache::lock('admin-process-queue', 60);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_queue_worker_launch_failure_releases_the_lock(): void
    {
        Process::fake(['*' => new \RuntimeException('Process launch unavailable')]);
        config(['queue.default' => 'database']);

        $this->actingAs($this->superAdmin())->post(route('admin.cron-jobs.process-queue'))
            ->assertSessionHasErrors('queue')->assertSessionMissing('status');
        $lock = Cache::lock('admin-process-queue', 60);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_synchronous_queue_does_not_launch_a_worker(): void
    {
        Process::fake();
        config(['queue.default' => 'sync']);
        $this->actingAs($this->superAdmin())->post(route('admin.cron-jobs.process-queue'))
            ->assertSessionHasErrors('queue');
        Process::assertNothingRan();
    }

    public function test_terminal_returns_worker_output_and_exit_code(): void
    {
        Process::fake(['*' => Process::result(output: "SendPaymentRefundEmailJob RUNNING\nSendPaymentRefundEmailJob DONE\n")]);
        config(['queue.default' => 'database']);

        $this->actingAs($this->superAdmin())->postJson(route('admin.cron-jobs.process-queue'))
            ->assertOk()->assertJson([
                'successful' => true,
                'output' => "SendPaymentRefundEmailJob RUNNING\nSendPaymentRefundEmailJob DONE\n",
                'exit_code' => 0,
            ]);
        $this->get(route('admin.cron-jobs'))->assertSee('Queue terminal')->assertSee('queue-worker-output');
    }

    public function test_terminal_returns_stderr_on_worker_failure(): void
    {
        Process::fake(['*' => Process::result(output: 'Starting', errorOutput: 'Worker failed', exitCode: 1)]);
        config(['queue.default' => 'database']);

        $this->actingAs($this->superAdmin())->postJson(route('admin.cron-jobs.process-queue'))
            ->assertUnprocessable()->assertJson([
                'successful' => false,
                'output' => "Starting\n\n[stderr]\nWorker failed\n",
                'exit_code' => 1,
            ]);
    }

    public function test_terminal_limits_large_output(): void
    {
        Process::fake(['*' => Process::result(output: str_repeat('x', 70000).'DONE')]);
        config(['queue.default' => 'database']);
        $response = $this->actingAs($this->superAdmin())->postJson(route('admin.cron-jobs.process-queue'))->assertOk();

        $this->assertSame(65536, strlen($response->json('output')));
        $this->assertStringEndsWith("DONE\n", $response->json('output'));
    }

    public function test_terminal_launch_failure_returns_json(): void
    {
        Process::fake(['*' => new \RuntimeException('Process launch unavailable')]);
        config(['queue.default' => 'database']);
        $this->actingAs($this->superAdmin())->postJson(route('admin.cron-jobs.process-queue'))
            ->assertUnprocessable()->assertJson(['successful' => false, 'output' => '', 'exit_code' => null]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }
}
