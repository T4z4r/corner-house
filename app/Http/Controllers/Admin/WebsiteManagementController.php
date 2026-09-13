<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        $keys = ['platform_airbnb_url', 'platform_booking_url', 'platform_vrbo_url'];

        $labels = [
            'platform_airbnb_url' => 'Airbnb listing URL',
            'platform_booking_url' => 'Booking.com listing URL',
            'platform_vrbo_url' => 'Vrbo listing URL',
        ];

        $errors = [];

        foreach ($keys as $key) {
            $value = $request->input($key);

            // Blank means "hide this platform" — store null so the public site
            // stops showing its link, not a broken one.
            if ($value === null || trim($value) === '') {
                $this->savePlatformSetting($key, null, $labels[$key]);

                continue;
            }

            $validator = Validator::make([$key => $value], [$key => ['url', 'max:500']]);

            // An invalid URL in one field must not block the other two from
            // saving. Report it and keep going.
            if ($validator->fails()) {
                $errors[$key] = $validator->errors()->first($key);

                continue;
            }

            $this->savePlatformSetting($key, $value, $labels[$key]);
        }

        $this->auditLogger->log('platforms.updated', 'settings', 'settings', 'platforms');

        return back()
            ->withErrors($errors)
            ->with('status', 'Platform links updated.');
    }

    /**
     * Save a platform URL through the model so the settings cache is
     * invalidated, creating the setting row if it does not exist yet.
     */
    private function savePlatformSetting(string $key, ?string $value, string $label): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['group' => 'website', 'label' => $label, 'cast' => 'string', 'value' => $value],
        );
    }
}
