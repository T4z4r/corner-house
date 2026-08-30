<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AddOnController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $items = AddOn::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.addons.index', ['items' => $items]);
    }

    public function create(): View
    {
        return view('admin.addons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:drinks,food,experience,other'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        AddOn::create($data);
        $this->auditLogger->log('add_on.created', 'add_ons', 'add_on', $data['slug']);

        return redirect()->route('admin.addons.index')->with('status', 'Add-on created.');
    }

    public function edit(AddOn $addon): View
    {
        return view('admin.addons.edit', ['item' => $addon]);
    }

    public function update(Request $request, AddOn $addon): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:drinks,food,experience,other'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        $addon->update($data);
        $this->auditLogger->log('add_on.updated', 'add_ons', 'add_on', (string) $addon->id);

        return redirect()->route('admin.addons.index')->with('status', 'Add-on updated.');
    }

    public function destroy(AddOn $addon): RedirectResponse
    {
        $addon->delete();
        $this->auditLogger->log('add_on.deleted', 'add_ons', 'add_on', (string) $addon->id);

        return redirect()->route('admin.addons.index')->with('status', 'Add-on deleted.');
    }

    public function toggle(AddOn $addon): RedirectResponse
    {
        $addon->update(['is_active' => ! $addon->is_active]);
        $this->auditLogger->log('add_on.toggled', 'add_ons', 'add_on', (string) $addon->id);

        return back()->with('status', 'Status updated.');
    }
}
