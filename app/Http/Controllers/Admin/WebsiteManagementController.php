<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteManagementController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $property = Property::query()->first();

        return view('admin.website.index', [
            'property' => $property,
            'settings' => Setting::allCached(),
        ]);
    }

    public function houseRules(): View
    {
        $property = Property::query()->first();

        return view('admin.website.house-rules', [
            'property' => $property,
        ]);
    }

    public function updateHouseRules(Request $request): RedirectResponse
    {
        $property = Property::query()->first();

        if (! $property) {
            return back()->withErrors(['error' => 'No property found.']);
        }

        $data = $request->validate([
            'smoking_allowed' => ['nullable', 'boolean'],
            'children_allowed' => ['nullable', 'boolean'],
            'parties_allowed' => ['nullable', 'boolean'],
            'pets_allowed' => ['nullable', 'string', 'in:yes,upon_request,no'],
            'check_in_from' => ['nullable', 'string', 'max:10'],
            'check_in_until' => ['nullable', 'string', 'max:10'],
            'check_out_from' => ['nullable', 'string', 'max:10'],
            'check_out_until' => ['nullable', 'string', 'max:10'],
            'custom_rules' => ['nullable', 'string'],
        ]);

        $old = $property->only(array_keys($data));
        $property->update($data);
        $new = $property->only(array_keys($data));

        $this->auditLogger->log('house_rules.updated', 'properties', 'property', (string) $property->id, $old, $new);

        return back()->with('status', 'House rules updated.');
    }

    public function content(): View
    {
        $property = Property::query()->first();

        return view('admin.website.content', [
            'property' => $property,
        ]);
    }

    public function updateContent(Request $request): RedirectResponse
    {
        $property = Property::query()->first();

        if (! $property) {
            return back()->withErrors(['error' => 'No property found.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'address_line_1' => ['nullable', 'string'],
            'address_line_2' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'postcode' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:2'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
        ]);

        $old = $property->only(array_keys($data));
        $property->update($data);
        $new = $property->only(array_keys($data));

        $this->auditLogger->log('website_content.updated', 'properties', 'property', (string) $property->id, $old, $new);

        return back()->with('status', 'Content updated.');
    }

    public function amenities(): View
    {
        $property = Property::query()->first();
        $allAmenities = Amenity::query()->where('is_active', true)->orderBy('name')->get();
        $propertyAmenityIds = $property?->amenities->pluck('id') ?? collect();

        return view('admin.website.amenities', [
            'property' => $property,
            'allAmenities' => $allAmenities,
            'propertyAmenityIds' => $propertyAmenityIds,
        ]);
    }

    public function updateAmenities(Request $request): RedirectResponse
    {
        $property = Property::query()->first();

        if (! $property) {
            return back()->withErrors(['error' => 'No property found.']);
        }

        $request->validate([
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
        ]);

        $property->amenities()->sync($request->input('amenity_ids', []));

        $this->auditLogger->log('website_amenities.updated', 'properties', 'property', (string) $property->id);

        return back()->with('status', 'Amenities updated.');
    }

    public function platforms(): View
    {
        return view('admin.website.platforms', [
            'settings' => Setting::allCached(),
        ]);
    }

    public function updatePlatforms(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_airbnb_url' => ['nullable', 'url', 'max:500'],
            'platform_booking_url' => ['nullable', 'url', 'max:500'],
            'platform_vrbo_url' => ['nullable', 'url', 'max:500'],
        ]);

        foreach ($data as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        $this->auditLogger->log('platforms.updated', 'settings', 'settings', 'platforms');

        return back()->with('status', 'Platform links updated.');
    }
}
