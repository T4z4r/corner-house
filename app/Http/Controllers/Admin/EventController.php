<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\Property;
use App\Services\AI\AiProviderService;
use App\Services\Area\LocalEventIntelligenceService;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    private const EVENT_CATEGORIES = ['event', 'events', 'local-event', 'local-events', 'area-event', 'area-events'];

    public function __construct(
        private readonly LocalEventIntelligenceService $eventIntelligence,
        private readonly AiProviderService $provider,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        $range = $request->string('range', 'upcoming')->toString();

        $query = KnowledgeBaseArticle::query()
            ->whereIn('category', self::EVENT_CATEGORIES)
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            });

        if ($range === 'past') {
            $query->whereDate('starts_at', '<', now()->startOfDay()->toDateString())->orderByDesc('starts_at');
        } elseif ($range === 'all') {
            $query->orderByDesc('starts_at');
        } else {
            $query->whereDate('starts_at', '>=', now()->startOfDay()->toDateString())->orderBy('starts_at');
        }

        return view('admin.events.index', [
            'items' => $query->paginate(20)->withQueryString(),
            'range' => $range,
            'aiConfigured' => $this->provider->openaiKey() !== null || $this->provider->claudeKey() !== null,
        ]);
    }

    public function create(): View
    {
        return view('admin.events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $event = KnowledgeBaseArticle::create([
            ...$this->payload($data),
            'source' => 'manual',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogger->log('events.created', 'events', 'knowledge_base_article', (string) $event->id);

        return redirect()->route('admin.events.index')->with('status', 'Event created.');
    }

    public function edit(KnowledgeBaseArticle $event): View
    {
        return view('admin.events.edit', ['item' => $event]);
    }

    public function update(Request $request, KnowledgeBaseArticle $event): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $event->update([
            ...$this->payload($data),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogger->log('events.updated', 'events', 'knowledge_base_article', (string) $event->id);

        return redirect()->route('admin.events.index')->with('status', 'Event updated.');
    }

    public function destroy(KnowledgeBaseArticle $event): RedirectResponse
    {
        $event->delete();
        $this->auditLogger->log('events.deleted', 'events', 'knowledge_base_article', (string) $event->id);

        return redirect()->route('admin.events.index')->with('status', 'Event deleted.');
    }

    public function toggle(KnowledgeBaseArticle $event): RedirectResponse
    {
        $event->update([
            'status' => $event->status === 'active' ? 'disabled' : 'active',
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogger->log('events.toggled', 'events', 'knowledge_base_article', (string) $event->id);

        return back()->with('status', 'Status updated.');
    }

    public function generate(): RedirectResponse
    {
        $property = Property::query()->where('status', 'active')->orderByDesc('is_primary')->first();

        if (! $property) {
            return back()->with('error', 'Set up an active property before generating local events.');
        }

        $result = $this->eventIntelligence->generateForProperty($property, auth()->id());

        $this->auditLogger->log(
            'events.ai_generated',
            'events',
            'knowledge_base_article',
            null,
            oldValues: [],
            newValues: ['created' => $result['created'], 'updated' => $result['updated'], 'summary' => $result['summary']],
        );

        if ($result['created'] === 0 && $result['updated'] === 0) {
            return back()->with('error', $result['summary']);
        }

        $message = sprintf('%s Added %d, updated %d.', $result['summary'], $result['created'], $result['updated']);

        return back()->with('status', $message);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            ...$data,
            'category' => 'event',
            'show_on_website' => (bool) ($data['show_on_website'] ?? true),
            'priority' => $data['priority'] ?? 1,
        ];
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:active,disabled'],
            'show_on_website' => ['nullable', 'boolean'],
        ];
    }
}
