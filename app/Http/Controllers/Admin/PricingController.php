<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingOverride;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Room;
use App\Services\Audit\AuditLogger;
use App\Services\Pricing\SeasonalPricingAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SeasonalPricingAutomationService $seasonalPricingAutomation,
    ) {}

    public function index(Request $request): View
    {
        $propertyId = $request->query('property_id');
        $ruleQuery = PricingRule::query()->with(['property', 'room'])->orderByDesc('priority');
        $overrideQuery = PricingOverride::query()->with(['room'])->orderByDesc('start_date');

        if ($propertyId) {
            $ruleQuery->where(fn ($q) => $q->where('property_id', $propertyId)->orWhereNull('property_id'));
        }

        return view('admin.pricing.index', [
            'rules' => $ruleQuery->get(),
            'overrides' => $overrideQuery->get(),
            'properties' => Property::query()->where('status', 'active')->get(),
            'rooms' => Room::query()->with('property')->orderBy('name')->get(),
            'selectedPropertyId' => $propertyId,
        ]);
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => ['nullable', 'exists:properties,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'name' => ['required', 'string', 'max:255'],
            'rule_type' => ['required', 'in:base,seasonal,holiday,occupancy,demand,competitor,event,last_minute,length_of_stay,weekday'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', 'integer', 'min:1', 'max:10'],
            'adjustment_type' => ['required', 'in:percent,amount,multiplier'],
            'adjustment_value' => ['required', 'numeric'],
            'minimum_stay' => ['nullable', 'integer', 'min:1'],
            'max_stay' => ['nullable', 'integer', 'min:1'],
            'occupancy_threshold' => ['nullable', 'numeric', 'between:0,100'],
            'days_before_checkin' => ['nullable', 'integer', 'min:0'],
            'apply_weekends_only' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $rule = PricingRule::create([
            ...$validated,
            'apply_weekends_only' => $request->boolean('apply_weekends_only'),
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        $this->auditLogger->log('pricing.rule_created', 'pricing', 'pricing_rule', (string) $rule->id, newValues: array_intersect_key($validated, array_flip(['name', 'rule_type', 'priority', 'adjustment_type', 'adjustment_value'])));

        return redirect()->route('admin.pricing.index')->with('status', 'Pricing rule created.');
    }

    public function updateRule(Request $request, PricingRule $rule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', 'integer', 'min:1', 'max:10'],
            'adjustment_type' => ['required', 'in:percent,amount,multiplier'],
            'adjustment_value' => ['required', 'numeric'],
            'minimum_stay' => ['nullable', 'integer', 'min:1'],
            'max_stay' => ['nullable', 'integer', 'min:1'],
            'occupancy_threshold' => ['nullable', 'numeric', 'between:0,100'],
            'days_before_checkin' => ['nullable', 'integer', 'min:0'],
            'apply_weekends_only' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $rule->update([
            ...$validated,
            'apply_weekends_only' => $request->boolean('apply_weekends_only'),
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        $this->auditLogger->log('pricing.rule_updated', 'pricing', 'pricing_rule', (string) $rule->id, newValues: ['id' => $rule->id]);

        return redirect()->route('admin.pricing.index')->with('status', 'Pricing rule updated.');
    }

    public function destroyRule(PricingRule $rule): RedirectResponse
    {
        $this->auditLogger->log('pricing.rule_deleted', 'pricing', 'pricing_rule', (string) $rule->id, newValues: ['name' => $rule->name]);
        $rule->delete();

        return redirect()->route('admin.pricing.index')->with('status', 'Pricing rule deleted.');
    }

    public function storeOverride(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'rate' => ['required', 'numeric', 'min:0'],
            'minimum_stay' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $override = PricingOverride::create([
            ...$validated,
            'is_enabled' => true,
            'created_by' => auth()->id(),
        ]);

        $this->auditLogger->log('pricing.override_created', 'pricing', 'pricing_override', (string) $override->id, newValues: ['room_id' => $override->room_id, 'rate' => $override->rate]);

        return redirect()->route('admin.pricing.index')->with('status', 'Rate override created.');
    }

    public function destroyOverride(PricingOverride $override): RedirectResponse
    {
        $this->auditLogger->log('pricing.override_deleted', 'pricing', 'pricing_override', (string) $override->id);
        $override->delete();

        return redirect()->route('admin.pricing.index')->with('status', 'Rate override deleted.');
    }

    public function generateSeasonalRules(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => ['nullable', 'exists:properties,id'],
        ]);

        $property = isset($validated['property_id'])
            ? Property::query()->where('status', 'active')->findOrFail((int) $validated['property_id'])
            : Property::query()->where('status', 'active')->orderBy('id')->first();

        if (! $property) {
            return redirect()->route('admin.pricing.index')->with('status', 'Create an active property before generating seasonal pricing.');
        }

        $result = $this->seasonalPricingAutomation->generateForProperty($property);

        $this->auditLogger->log('pricing.ai_generated', 'pricing', 'pricing_rule', (string) $property->id, newValues: [
            'property_id' => $property->id,
            'created' => $result['created'],
            'updated' => $result['updated'],
        ]);

        return redirect()
            ->route('admin.pricing.index', ['property_id' => $property->id])
            ->with('status', sprintf(
                '%s Generated %d new seasonal rule(s) and updated %d existing rule(s).',
                $result['summary'],
                $result['created'],
                $result['updated'],
            ));
    }
}
