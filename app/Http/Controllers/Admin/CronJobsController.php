<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CronJobRun;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

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
        'PushBeds24RatesJob' => 'Push Beds24 Rates',
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
