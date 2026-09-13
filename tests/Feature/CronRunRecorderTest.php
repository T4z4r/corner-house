<?php

namespace Tests\Feature;

use App\Jobs\ExpireBookingHoldsJob;
use App\Jobs\GenerateRevenueSnapshotJob;
use App\Models\CronJobRun;
use App\Services\Booking\BookingHoldService;
use App\Services\Cron\CronRunRecorder;
use App\Services\Revenue\RevenueAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CronRunRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_and_finish_record_a_successful_run(): void
    {
        $run = CronRunRecorder::start('DummyJob');

        $this->assertSame('running', $run->status);
        $this->assertNotNull($run->started_at);

        CronRunRecorder::finish($run);

        $run->refresh();

        $this->assertSame('success', $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertNotNull($run->duration_ms);
        $this->assertNull($run->error);
    }

    public function test_track_records_a_failure_and_rethrows(): void
    {
        try {
            CronRunRecorder::track('DummyJob', static function (): void {
                throw new RuntimeException('boom');
            });

            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertDatabaseHas('cron_job_runs', [
            'job' => 'DummyJob',
            'status' => 'failed',
            'error' => 'boom',
        ]);
    }

    public function test_job_with_catching_internal_errors_records_success(): void
    {
        $this->mock(BookingHoldService::class)
            ->shouldReceive('expireExpiredHolds')
            ->once()
            ->andReturn(2);

        (new ExpireBookingHoldsJob)->handle(app(BookingHoldService::class));

        $this->assertDatabaseHas('cron_job_runs', [
            'job' => 'ExpireBookingHoldsJob',
            'status' => 'success',
        ]);
    }

    public function test_job_with_a_thrown_exception_records_failure_and_rethrows(): void
    {
        $this->mock(RevenueAnalyticsService::class)
            ->shouldReceive('snapshotForDate')
            ->once()
            ->andThrow(new RuntimeException('snapshot boom'));

        try {
            (new GenerateRevenueSnapshotJob)->handle(app(RevenueAnalyticsService::class));

            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('snapshot boom', $e->getMessage());
        }

        $this->assertDatabaseHas('cron_job_runs', [
            'job' => 'GenerateRevenueSnapshotJob',
            'status' => 'failed',
            'error' => 'snapshot boom',
        ]);
    }
}