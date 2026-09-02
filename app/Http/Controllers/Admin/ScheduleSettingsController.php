<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleSettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $settings = Setting::query()->where('group', 'schedule')->orderBy('key')->get();

        return view('admin.schedule-settings', [
            'settings' => $settings,
            'frequencies' => $this->frequencyOptions(),
        ]);
    }

    public function update(): RedirectResponse
    {
        $settings = Setting::query()->where('group', 'schedule')->get();

        foreach ($settings as $setting) {
            $key = $setting->key;

            if ($setting->cast === 'boolean') {
                $value = request()->boolean($key) ? '1' : '0';
            } else {
                $value = request()->input($key, $setting->value);
            }

            if ($setting->value !== $value) {
                $old = $setting->value;
                $setting->update(['value' => $value]);
                $this->auditLogger->log(
                    'settings.updated',
                    'settings',
                    'setting',
                    (string) $setting->id,
                    ['value' => $old],
                    ['value' => $value],
                );
            }
        }

        return back()->with('status', 'Schedule settings updated.');
    }

    /**
     * @return array<string, string>
     */
    private function frequencyOptions(): array
    {
        return [
            'every_five_minutes' => 'Every 5 minutes',
            'every_fifteen_minutes' => 'Every 15 minutes',
            'every_thirty_minutes' => 'Every 30 minutes',
            'hourly' => 'Every hour',
            'twice_daily' => 'Twice daily (06:00 & 18:00)',
            'daily' => 'Once daily (06:00)',
        ];
    }
}
