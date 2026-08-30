<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\Revenue\RevenueAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function __construct(private readonly RevenueAnalyticsService $analytics) {}

    public function index(Request $request): View
    {
        $propertyId = $request->integer('property_id') ?: null;
        $stats = $this->analytics->dashboardStats($propertyId);
        $series = $this->analytics->monthlySeries($propertyId);
        $sources = $this->analytics->bookingsBySource($propertyId);

        return view('admin.revenue.index', [
            'stats' => $stats,
            'series' => $series,
            'sources' => $sources,
            'properties' => Property::query()->orderBy('name')->get(),
            'propertyId' => $propertyId,
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $propertyId = $request->integer('property_id') ?: null;

        return response()->json([
            'monthly' => $this->analytics->monthlySeries($propertyId),
            'sources' => $this->analytics->bookingsBySource($propertyId),
        ]);
    }
}
