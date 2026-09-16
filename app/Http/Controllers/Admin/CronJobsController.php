<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CronJobRun;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\View\View;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

class CronJobsController extends Controller
{
    /**
     * @var array<string, string> job basename => friendly label
     */
    public const JOB_DEFINITIONS = [
        'ExpireBookingHoldsJob' => 'Expire Booking Holds',
        'SendPreArrivalMessageJob' => 'Pre-arrival Messages',
        'SendCheckInNotificationJob' => 'Check-in Notifications',
        'SendCheckoutNotificationJob' => 'Check-out Notifications',
        'GenerateRevenueSnapshotJob' => 'Revenue Snapshots',
        'GenerateSeasonalPricingJob' => 'Seasonal Pricing',
        'SyncBeds24BookingsJob' => 'Beds24 Bookings Sync',
        'SyncBeds24MessagesJob' => 'Beds24 Messages Sync',
    ];

    public function index(Request $request): View
    {
        $runs = CronJobRun::query()
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $jobs = collect(self::JOB_DEFINITIONS)
            ->map(function (string $label, string $job): array {
                return [
                    'name' => $job,
                    'label' => $label,
                    'last_run' => CronJobRun::query()->where('job', $job)->latest('id')->first(),
                    'success_count' => CronJobRun::query()->where('job', $job)->where('status', 'success')->count(),
                    'failure_count' => CronJobRun::query()->where('job', $job)->where('status', 'failed')->count(),
                ];
            })
            ->values();

        $cadence = $this->jobCadence();

        $summary = [
            'last_24h' => CronJobRun::query()->where('started_at', '>=', now()->subDay())->count(),
            'success' => CronJobRun::query()->where('status', 'success')->count(),
            'failed' => CronJobRun::query()->where('status', 'failed')->count(),
            'running' => CronJobRun::query()->where('status', 'running')->count(),
        ];

        return view('admin.cron-jobs', compact('runs', 'jobs', 'cadence', 'summary'));
    }

    /**
     * Queue a scheduled job for an immediate run.
     */
    public function run(string $job): RedirectResponse
    {
        $label = self::JOB_DEFINITIONS[$job] ?? null;

        if ($label === null) {
            abort(404);
        }

        $class = "App\\Jobs\\{$job}";

        abort_unless(class_exists($class), 404);

        dispatch(new $class);

        return redirect()->route('admin.cron-jobs')
            ->with('status', "{$label} has been queued to run.");
    }

    public function processQueue(): RedirectResponse
    {
        $connection = (string) config('queue.default');
        if (in_array(config("queue.connections.{$connection}.driver"), ['sync', 'null', 'deferred', 'background'], true)) {
            return back()->withErrors(['queue' => 'The configured queue does not support a worker. Configure a database or Redis queue first.']);
        }

        $lock = Cache::lock('admin-process-queue', 60);
        if (! $lock->get()) {
            return back()->withErrors(['queue' => 'A queue batch is already running. Please wait before trying again.']);
        }

        try {
            $php = (new PhpExecutableFinder)->find(false);
            if (! $php) {
                return back()->withErrors(['queue' => 'The PHP command-line executable could not be found on this server.']);
            }

            $result = Process::path(base_path())->timeout(25)->run([
                $php, base_path('artisan'), 'queue:work', $connection,
                '--stop-when-empty', '--tries=3', '--max-time=15', '--max-jobs=25',
                '--timeout=15', '--sleep=1', '--no-interaction',
            ]);

            if (! $result->successful()) {
                Log::warning('Manual queue worker exited unsuccessfully', [
                    'exit_code' => $result->exitCode(),
                    'error' => $result->errorOutput(),
                ]);

                return back()->withErrors(['queue' => 'The worker did not finish successfully. Check the application logs and failed jobs before trying again.']);
            }

            return back()->with('status', 'Queue batch finished. If jobs remain, run another batch. Individual jobs may have failed; check the application logs and job history.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['queue' => 'The queue batch could not finish within the web request. Use the cPanel cron for long-running jobs.']);
        } finally {
            $lock->release();
        }
    }

    /**
     * Map each known job to its schedule expression from the registered schedule.
     *
     * @return Collection<string, string>
     */
    private function jobCadence(): Collection
    {
        $jobNames = array_keys(self::JOB_DEFINITIONS);

        return collect(app(Schedule::class)->events())
            ->mapWithKeys(function (Event $event) use ($jobNames): array {
                $summary = (string) $event->getSummaryForDisplay();

                foreach ($jobNames as $job) {
                    if (str_contains($summary, $job)) {
                        return [$job => $event->expression];
                    }
                }

                return [];
            });
    }
}
