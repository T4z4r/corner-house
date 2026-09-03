<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $grouped = $this->groupedSettings();

        return view('admin.settings', ['grouped' => $grouped, 'tab' => $grouped->keys()->first() ?? 'general']);
    }

    public function mail(): View
    {
        return view('admin.settings', $this->groupViewData(
            title: 'Email settings',
            subtitle: 'Configure the outgoing mailer and sender details',
            group: 'mail',
        ));
    }

    public function notifications(): View
    {
        return view('admin.settings', $this->groupViewData(
            title: 'Email notifications',
            subtitle: 'Choose which automated email events are sent',
            group: 'notifications',
        ));
    }

    public function website(): View
    {
        $settings = Setting::query()
            ->where('group', 'website')
            ->get()
            ->keyBy('key');

        return view('admin.settings.website', [
            'settings' => $settings,
            'pageTitle' => 'Website settings',
            'pageSubtitle' => 'What visitors see on your public website — change it here in plain English',
        ]);
    }

    public function stripe(): View
    {
        return view('admin.settings', $this->groupViewData(
            title: 'Stripe settings',
            subtitle: 'Configure Stripe payment processing and API keys',
            group: 'stripe',
        ));
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'max:5120'],
            'key' => ['required', 'string'],
        ]);

        $path = $request->file('file')->store('website', 'public');

        return response()->json(['ok' => true, 'path' => $path]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = Setting::query()->get();

        foreach ($settings as $setting) {
            if ($setting->isSecret()) {
                $incoming = $request->input($setting->key);
                if (! is_string($incoming) || trim($incoming) === '') {
                    continue;
                }
                $value = Setting::encryptSecret($incoming);
            } elseif ($setting->cast === 'boolean') {
                if (! $request->exists($setting->key)) {
                    continue;
                }
                $value = $request->boolean($setting->key) ? '1' : '0';
            } elseif (! $request->has($setting->key)) {
                continue;
            } else {
                $value = $request->input($setting->key);
                if ($setting->cast === 'json') {
                    // The generic settings form submits the stored JSON string
                    // (or a plain array when a JSON-cast setting is posted as a
                    // variable-length field). json_encode()-ing an already-valid
                    // JSON string would double-encode it and break json_decode()
                    // downstream, so only encode when it is not yet valid JSON.
                    if (is_string($value) && json_validate($value)) {
                        // already valid JSON — store verbatim
                    } else {
                        $value = json_encode($value);
                    }
                }
            }

            if ($setting->value !== $value) {
                $old = $setting->isSecret() ? '[hidden]' : $setting->value;
                $setting->update(['value' => $value]);
                $this->auditLogger->log(
                    'settings.updated',
                    'settings',
                    'setting',
                    (string) $setting->id,
                    ['value' => $old],
                    ['value' => $setting->isSecret() ? '[hidden]' : $value],
                );
            }
        }

        return back()->with('status', 'Settings updated successfully.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string'],
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'],
            'label' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string'],
            'cast' => ['required', 'in:string,boolean,integer,decimal,json,secret'],
        ]);

        if ($validated['cast'] === 'secret' && filled($validated['value'] ?? null)) {
            $validated['value'] = Setting::encryptSecret($validated['value']);
        }

        Setting::create($validated);
        $this->auditLogger->log('settings.created', 'settings', 'setting', null, newValues: $validated);

        return back()->with('status', 'Setting created.');
    }

    /**
     * @return array{grouped: Collection<string, Collection<int, Setting>>, tab: string, pageTitle: string, pageSubtitle: string, singleGroup: bool}
     */
    private function groupViewData(string $title, string $subtitle, string $group): array
    {
        $grouped = $this->groupedSettings($group);

        return [
            'grouped' => $grouped,
            'tab' => $group,
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'singleGroup' => true,
        ];
    }

    /**
     * @return Collection<string, Collection<int, Setting>>
     */
    private function groupedSettings(?string $group = null): Collection
    {
        $settings = Setting::query()
            ->when($group, fn ($query) => $query->where('group', $group))
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return $settings->groupBy('group');
    }
}
