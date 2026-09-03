<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\Review;
use App\Services\Audit\AuditLogger;
use App\Services\Beds24\Beds24Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $query = Review::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('quote', 'like', "%{$search}%")
                        ->orWhere('cite', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->orderBy('sort_order')
            ->orderByDesc('id');

        $items = $query->paginate(20)->withQueryString();

        return view('admin.reviews.index', [
            'items' => $items,
            'approvedCount' => Review::approved()->count(),
            'hiddenCount' => Review::query()->where('status', Review::STATUS_HIDDEN)->count(),
            'accounts' => $this->beds24Accounts(),
            'beds24Rooms' => $this->beds24Rooms(),
        ]);
    }

    public function create(): View
    {
        return view('admin.reviews.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stars' => ['required', 'integer', 'between:1,5'],
            'quote' => ['required', 'string', 'max:5000'],
            'cite' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:approved,hidden'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['status'] = (string) ($data['status'] ?? Review::STATUS_HIDDEN);
        $data['source'] = 'manual';

        $review = Review::create($data);
        $this->auditLogger->log('review.created', 'reviews', 'review', (string) $review->id);

        return redirect()->route('admin.reviews.index')->with('status', 'Review created.');
    }

    public function edit(Review $review): View
    {
        return view('admin.reviews.edit', ['item' => $review]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'stars' => ['required', 'integer', 'between:1,5'],
            'quote' => ['required', 'string', 'max:5000'],
            'cite' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:approved,hidden'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['status'] = (string) ($data['status'] ?? $review->status);

        $review->update($data);
        $this->auditLogger->log('review.updated', 'reviews', 'review', (string) $review->id);

        return redirect()->route('admin.reviews.index')->with('status', 'Review updated.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();
        $this->auditLogger->log('review.deleted', 'reviews', 'review', (string) $review->id);

        return redirect()->route('admin.reviews.index')->with('status', 'Review deleted.');
    }

    public function toggle(Review $review): RedirectResponse
    {
        $review->update([
            'status' => $review->status === Review::STATUS_APPROVED ? Review::STATUS_HIDDEN : Review::STATUS_APPROVED,
        ]);
        $this->auditLogger->log('review.toggled', 'reviews', 'review', (string) $review->id);

        return back()->with('status', 'Status updated.');
    }

    public function importFromAirbnb(Request $request, Beds24Client $client): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:channel_accounts,id'],
            'beds24_room_id' => ['required', 'string', 'max:100'],
        ]);

        $account = ChannelAccount::query()->findOrFail($data['account_id']);
        if ($account->provider !== 'beds24') {
            return back()->withErrors(['error' => 'Only Beds24 accounts can import Airbnb reviews.']);
        }

        $payload = $client->get($account, 'channels/airbnb/reviews', [
            'roomId' => $data['beds24_room_id'],
        ]);

        $imported = 0;
        foreach ($this->extractAirbnbReviews($payload) as $row) {
            $sourceId = data_get($row, 'id');
            if ($sourceId === null) {
                continue;
            }

            $base = Review::query()->where('source', 'airbnb')->where('source_id', (string) $sourceId)->exists();

            if ($base) {
                continue;
            }

            Review::create($this->mapAirbnbReview($row));
            $imported++;
        }

        $this->auditLogger->log('review.imported', 'reviews', 'review', null, newValues: ['imported' => $imported]);

        if ($imported === 0) {
            return back()->with('status', 'No new Airbnb reviews to import — everything is already in the table.');
        }

        return back()->with('status', sprintf('Imported %d Airbnb review%s as hidden. Approve them to show on the website.', $imported, $imported === 1 ? '' : 's'));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapAirbnbReview(array $row): array
    {
        $overall = (float) data_get($row, 'overall_rating', data_get($row, 'scoring.review_score', 5));
        $stars = max(1, min(5, (int) round($overall > 5 ? $overall / 2 : $overall)));

        $quote = trim((string) data_get($row, 'public_review', ''));
        $date = data_get($row, 'first_completed_at', data_get($row, 'submitted_at'));
        $reviewer = data_get($row, 'reviewer.name', data_get($row, 'reviewer_name', data_get($row, 'guest_name', '')));

        $cite = trim((string) $reviewer);
        if ($date !== null) {
            $formatted = Carbon::parse($date)->format('F Y');
            $cite = $cite !== '' ? $cite.', '.$formatted : $formatted;
        }

        return [
            'stars' => $stars,
            'quote' => $quote !== '' ? $quote : 'A guest stayed at Corner House.',
            'cite' => $cite !== '' ? $cite : null,
            'status' => Review::STATUS_HIDDEN,
            'source' => 'airbnb',
            'source_id' => (string) data_get($row, 'id'),
            'sort_order' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractAirbnbReviews(array $payload): array
    {
        $rows = data_get($payload, 'data', []);
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, static fn ($row): bool => is_array($row)));
    }

    /**
     * @return Collection<int, ChannelAccount>
     */
    private function beds24Accounts(): Collection
    {
        return ChannelAccount::query()
            ->where('provider', 'beds24')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, array{beds24_room_id: string, label: string}>
     */
    private function beds24Rooms(): Collection
    {
        return ChannelMapping::query()
            ->where('provider', 'beds24')
            ->whereNotNull('external_room_id')
            ->whereNotNull('room_id')
            ->with('room')
            ->orderBy('external_room_id')
            ->get()
            ->unique('external_room_id')
            ->values()
            ->map(static function (ChannelMapping $mapping): array {
                return [
                    'beds24_room_id' => (string) $mapping->external_room_id,
                    'label' => trim(($mapping->room?->name ?? 'Room').' (Beds24 '.$mapping->external_room_id.')'),
                ];
            });
    }
}
