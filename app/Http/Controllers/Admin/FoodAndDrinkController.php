<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodAndDrink;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FoodAndDrinkController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $query = FoodAndDrink::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $request->string('category')->toString()))
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->orderBy('sort_order')
            ->orderBy('name');

        $items = $query->paginate(20)->withQueryString();

        return view('admin.food-drink.index', [
            'items' => $items,
            'categories' => FoodAndDrink::query()
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
        ]);
    }

    public function create(): View
    {
        return view('admin.food-drink.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:restaurant,cafe,pub,takeaway,butcher,other'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:500'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active', true);

        FoodAndDrink::create($data);
        $this->auditLogger->log('food_and_drink.created', 'food_and_drinks', 'food_and_drink', $data['slug']);

        return redirect()->route('admin.food-drink.index')->with('status', 'Establishment created.');
    }

    public function edit(FoodAndDrink $foodAndDrink): View
    {
        return view('admin.food-drink.edit', ['item' => $foodAndDrink]);
    }

    public function update(Request $request, FoodAndDrink $foodAndDrink): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:restaurant,cafe,pub,takeaway,butcher,other'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:500'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active', true);

        $foodAndDrink->update($data);
        $this->auditLogger->log('food_and_drink.updated', 'food_and_drinks', 'food_and_drink', (string) $foodAndDrink->id);

        return redirect()->route('admin.food-drink.index')->with('status', 'Establishment updated.');
    }

    public function destroy(FoodAndDrink $foodAndDrink): RedirectResponse
    {
        $foodAndDrink->delete();
        $this->auditLogger->log('food_and_drink.deleted', 'food_and_drinks', 'food_and_drink', (string) $foodAndDrink->id);

        return redirect()->route('admin.food-drink.index')->with('status', 'Establishment deleted.');
    }

    public function toggle(FoodAndDrink $foodAndDrink): RedirectResponse
    {
        $foodAndDrink->update(['is_active' => ! $foodAndDrink->is_active]);
        $this->auditLogger->log('food_and_drink.toggled', 'food_and_drinks', 'food_and_drink', (string) $foodAndDrink->id);

        return back()->with('status', 'Status updated.');
    }
}
