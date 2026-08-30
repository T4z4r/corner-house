<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ChannelAccount;
use App\Models\ChannelSyncLog;
use App\Services\Revenue\RevenueAnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly RevenueAnalyticsService $analytics) {}

    public function index(): View
    {
        $stats = $this->analytics->dashboardStats();
        $series = $this->analytics->monthlySeries();
        $sources = $this->analytics->bookingsBySource();

        $recentAuditLogs = AuditLog::query()
            ->with('user')
            ->latest()
            ->limit(6)
            ->get();

        $channelAccount = ChannelAccount::query()->latest()->first();
        $lastSync = ChannelSyncLog::query()->where('status', 'success')->latest('completed_at')->first();

        return view('admin.dashboard', [
            'stats' => $stats,
            'series' => $series,
            'sources' => $sources,
            'recentAuditLogs' => $recentAuditLogs,
            'today' => now()->toDateString(),
            'channelAccount' => $channelAccount,
            'lastSync' => $lastSync,
        ]);
    }
}
