<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncBeds24BookingsJob;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\ChannelSyncLog;
use App\Models\PricingOverride;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Audit\AuditLogger;
use App\Services\Beds24\Beds24AuthService;
use App\Services\Beds24\Beds24BookingPublisher;
use App\Services\Beds24\Beds24ChannelProvider;
use App\Services\Beds24\Beds24Client;
use App\Services\Beds24\Beds24PricingPublisher;
use App\Services\Beds24\Beds24PropertyPublisher;
use App\Services\Beds24\Beds24SyncService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('admin.channels.integrations');
    }

    public function integrations(Request $request): View
    {
        return view('admin.channels.integrations', [
            'swaggerUrl' => 'https://beds24.com/api/v2/#/',
            ...$this->channelPageData($request),
        ]);
    }

    public function setupPage(Request $request): View
    {
        return view('admin.channels.setup', [
            ...$this->channelPageData($request),
        ]);
    }

    public function airbnb(Request $request, Beds24Client $client): View
    {
        $accounts = ChannelAccount::query()
            ->where('provider', 'beds24')
            ->withCount('mappings')
            ->orderBy('name')
            ->get();
        $properties = Property::query()->orderBy('name')->get(['id', 'name']);
        $rooms = Room::query()->orderBy('name')->get(['id', 'name', 'property_id']);

        $selectedAccount = $this->selectedBeds24Account($accounts, $request->query('account_id'));
        $beds24Properties = $this->beds24Properties();
        $beds24Rooms = $this->beds24Rooms();
        $users = [];
        $listings = [];
        $selectedListing = null;
        $reviews = [];
        $usersError = null;
        $listingsError = null;
        $reviewsError = null;
        $selectedUserId = null;
        $selectedRoomId = null;

        if ($selectedAccount instanceof ChannelAccount) {
            try {
                $usersPayload = $client->get($selectedAccount, 'channels/airbnb/users');
                $users = $this->extractAirbnbUsers($usersPayload);
                $selectedUserId = (string) ($request->query('airbnb_user_id') ?: ($users[0]['airbnb_user_id'] ?? ''));
            } catch (\Throwable $e) {
                $usersError = $e->getMessage();
            }

            if ($selectedUserId !== null && $selectedUserId !== '') {
                try {
                    $listingsPayload = $client->get($selectedAccount, 'channels/airbnb/listings', [
                        'airbnbUserId' => $selectedUserId,
                    ]);
                    $listings = $this->extractAirbnbListings($listingsPayload);
                } catch (\Throwable $e) {
                    $listingsError = $e->getMessage();
                }
            }

            $selectedListingId = (string) ($request->query('airbnb_listing_id') ?: ($listings[0]['airbnb_listing_id'] ?? ''));

            if ($selectedUserId !== null && $selectedUserId !== '' && $selectedListingId !== '') {
                try {
                    $listingPayload = $client->get($selectedAccount, 'channels/airbnb/listings', [
                        'airbnbUserId' => $selectedUserId,
                        'airbnbListingId' => $selectedListingId,
                    ]);
                    $selectedListing = $this->extractAirbnbListingDetail($listingPayload);
                    $selectedRoomId = (string) ($request->query('room_id') ?: ($selectedListing['room_id'] ?? ''));
                } catch (\Throwable $e) {
                    $listingsError = $e->getMessage();
                }
            }

            if ($selectedRoomId !== null && $selectedRoomId !== '') {
                try {
                    $reviewsPayload = $client->get($selectedAccount, 'channels/airbnb/reviews', [
                        'roomId' => $selectedRoomId,
                    ]);
                    $reviews = $this->extractAirbnbReviews($reviewsPayload);
                } catch (\Throwable $e) {
                    $reviewsError = $e->getMessage();
                }
            }
        }

        $stats = $this->buildAirbnbStats($selectedAccount, $users, $listings, $reviews, $selectedListing);

        return view('admin.channels.airbnb', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'users' => $users,
            'listings' => $listings,
            'selectedListing' => $selectedListing,
            'reviews' => $reviews,
            'usersError' => $usersError,
            'listingsError' => $listingsError,
            'reviewsError' => $reviewsError,
            'selectedUserId' => $selectedUserId,
            'selectedListingId' => $selectedListingId ?? null,
            'selectedRoomId' => $selectedRoomId ?? null,
            'properties' => $properties,
            'rooms' => $rooms,
            'beds24Properties' => $beds24Properties,
            'beds24Rooms' => $beds24Rooms,
            'stats' => $stats,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $users
     * @param  array<int, array<string, mixed>>  $listings
     * @param  array<int, array<string, mixed>>  $reviews
     * @param  array<string, mixed>|null  $selectedListing
     * @return array<string, mixed>
     */
    private function buildAirbnbStats(?ChannelAccount $selectedAccount, array $users, array $listings, array $reviews, ?array $selectedListing): array
    {
        $linkedListings = collect($listings)->filter(fn ($listing): bool => ! empty($listing['room_id']))->count();
        $enabledListings = collect($listings)->filter(fn ($listing): bool => (bool) ($listing['enabled'] ?? false))->count();

        $reviewScores = collect($reviews)->map(fn ($review): ?float => $this->extractReviewScore($review))
            ->filter(fn (?float $score): bool => $score !== null);

        $airbnbListing = is_array($selectedListing['airbnb_listing'] ?? null) ? $selectedListing['airbnb_listing'] : [];

        return [
            'user_count' => count($users),
            'listing_count' => count($listings),
            'linked_count' => $linkedListings,
            'enabled_count' => $enabledListings,
            'review_count' => count($reviews),
            'review_avg_score' => $reviewScores->isNotEmpty() ? round($reviewScores->average(), 1) : null,
            'bedrooms' => $airbnbListing['bedrooms'] ?? null,
            'beds' => $airbnbListing['beds'] ?? null,
            'mappings' => $selectedAccount?->mappings_count ?? 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $review
     */
    private function extractReviewScore(array $review): ?float
    {
        $score = data_get($review, 'scoring.review_score', data_get($review, 'scoring.reviewScore', data_get($review, 'review_score')));

        return is_numeric($score) ? (float) $score : null;
    }

    public function booking(Request $request, Beds24Client $client): View
    {
        $accounts = ChannelAccount::query()
            ->where('provider', 'beds24')
            ->withCount('mappings')
            ->orderBy('name')
            ->get();
        $selectedAccount = $this->selectedBeds24Account($accounts, $request->query('account_id'));
        $beds24Rooms = $this->beds24Rooms();

        $selectedRoomId = null;
        $reviews = [];
        $reviewsError = null;

        if ($selectedAccount instanceof ChannelAccount) {
            $selectedRoomId = (string) ($request->query('room_id') ?: ($beds24Rooms[0]['beds24_room_id'] ?? ''));

            if ($selectedRoomId !== '') {
                try {
                    $reviewsPayload = $client->get($selectedAccount, 'channels/booking/reviews', [
                        'roomId' => $selectedRoomId,
                    ]);
                    $reviews = $this->extractBookingReviews($reviewsPayload);
                } catch (\Throwable $e) {
                    $reviewsError = $e->getMessage();
                }
            }
        }

        return view('admin.channels.booking', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'beds24Rooms' => $beds24Rooms,
            'reviews' => $reviews,
            'reviewsError' => $reviewsError,
            'selectedRoomId' => $selectedRoomId,
        ]);
    }

    public function vrbo(Request $request, Beds24Client $client): View
    {
        $accounts = ChannelAccount::query()
            ->where('provider', 'beds24')
            ->withCount('mappings')
            ->orderBy('name')
            ->get();
        $selectedAccount = $this->selectedBeds24Account($accounts, $request->query('account_id'));
        $beds24Rooms = $this->beds24Rooms();

        $bookings = [];
        $bookingsError = null;
        $selectedBooking = null;
        $selectedBookingId = null;

        if ($selectedAccount instanceof ChannelAccount) {
            $selectedBookingId = (string) $request->query('booking_id', '');

            try {
                $bookingsPayload = $client->get($selectedAccount, 'bookings', [
                    'apiSourceId' => 30,
                    'includeGuestDetails' => true,
                    'includeRoomDetails' => true,
                ]);
                $bookings = $this->extractVrboBookings($bookingsPayload);
            } catch (\Throwable $e) {
                $bookingsError = $e->getMessage();
            }

            if ($selectedBookingId !== '') {
                $selectedBooking = collect($bookings)->firstWhere('id', $selectedBookingId);
            }
        }

        if ($selectedBooking === null) {
            $selectedBooking = $bookings[0] ?? null;
            $selectedBookingId = $selectedBooking['id'] ?? $selectedBookingId;
        }

        return view('admin.channels.vrbo', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'beds24Rooms' => $beds24Rooms,
            'bookings' => $bookings,
            'bookingsError' => $bookingsError,
            'selectedBooking' => $selectedBooking,
            'selectedBookingId' => $selectedBookingId,
        ]);
    }

    public function publishPricingRule(PricingRule $rule, Beds24PricingPublisher $publisher): RedirectResponse
    {
        if ($publisher->postRule($rule)) {
            return back()->with('status', 'Pricing rule posted to Beds24.');
        }

        return back()->with('status', 'Pricing rule could not be posted to Beds24.');
    }

    public function publishPricingOverride(PricingOverride $override, Beds24PricingPublisher $publisher): RedirectResponse
    {
        if ($publisher->postOverride($override)) {
            return back()->with('status', 'Rate override posted to Beds24.');
        }

        return back()->with('status', 'Rate override could not be posted to Beds24.');
    }

    public function publishProperty(Property $property, Beds24PropertyPublisher $publisher): RedirectResponse
    {
        if ($publisher->postProperty($property)) {
            return back()->with('status', 'Property posted to Beds24.');
        }

        return back()->with('status', 'Property could not be posted to Beds24.');
    }

    public function publishBooking(Reservation $reservation, Beds24BookingPublisher $publisher): RedirectResponse
    {
        return $this->publishReservation(
            $reservation,
            $publisher,
            'Booking posted to Beds24.',
            'Booking could not be posted to Beds24.',
        );
    }

    public function publishGuests(Reservation $reservation, Beds24BookingPublisher $publisher): RedirectResponse
    {
        return $this->publishReservation(
            $reservation,
            $publisher,
            'Guest details posted to Beds24.',
            'Guest details could not be posted to Beds24.',
        );
    }

    public function importPrices(Request $request, Beds24SyncService $sync): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:channel_accounts,id'],
        ]);

        $account = ChannelAccount::query()->findOrFail($data['account_id']);
        if ($account->provider !== 'beds24') {
            return back()->withErrors(['error' => 'Only Beds24 accounts can import prices.']);
        }

        $result = $sync->syncCalendar($account);

        return back()->with('status', sprintf(
            'Imported %d price override%s and %d calendar block%s from Beds24.',
            $result['overrides'],
            $result['overrides'] === 1 ? '' : 's',
            $result['blocks'],
            $result['blocks'] === 1 ? '' : 's',
        ));
    }

    public function airbnbAction(Request $request, Beds24Client $client): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:channel_accounts,id'],
            'action' => ['required', 'in:importAsNewProperty,importToExistingProperty,connectToExistingRoom,disconnectRoom'],
            'airbnb_user_id' => ['nullable', 'string', 'max:100'],
            'airbnb_listing_id' => ['nullable', 'string', 'max:100'],
            'connect' => ['nullable', 'in:none,inventory,limited,full'],
            'beds24_property_id' => ['nullable', 'string', 'max:100'],
            'beds24_room_id' => ['nullable', 'string', 'max:100'],
            'import_blocked_dates' => ['nullable', 'boolean'],
            'import_bookings' => ['nullable', 'boolean'],
        ]);

        $account = ChannelAccount::query()->findOrFail($data['account_id']);
        if ($account->provider !== 'beds24') {
            return back()->withErrors(['error' => 'Only Beds24 accounts can send Airbnb actions.']);
        }

        if (in_array($data['action'], ['importAsNewProperty', 'importToExistingProperty', 'connectToExistingRoom'], true)) {
            if (empty($data['airbnb_user_id']) || empty($data['airbnb_listing_id'])) {
                return back()->withErrors(['error' => 'Select an Airbnb user and listing before sending this action.']);
            }
        }

        $payload = [
            'action' => $data['action'],
        ];

        if (! empty($data['airbnb_user_id'])) {
            $payload['airbnbUserId'] = $data['airbnb_user_id'];
        }
        if (! empty($data['airbnb_listing_id'])) {
            $payload['airbnbListingId'] = $data['airbnb_listing_id'];
        }
        if (! empty($data['connect'])) {
            $payload['connect'] = $data['connect'];
        }
        if (array_key_exists('import_blocked_dates', $data)) {
            $payload['importBlockedDates'] = (bool) $data['import_blocked_dates'];
        }
        if (array_key_exists('import_bookings', $data)) {
            $payload['importBookings'] = (bool) $data['import_bookings'];
        }

        if ($data['action'] === 'importToExistingProperty') {
            if (empty($data['beds24_property_id'])) {
                return back()->withErrors(['error' => 'Beds24 property ID is required for importToExistingProperty.']);
            }

            $payload['propertyId'] = (int) $data['beds24_property_id'];
        }

        if (in_array($data['action'], ['connectToExistingRoom', 'disconnectRoom'], true)) {
            if (empty($data['beds24_room_id'])) {
                return back()->withErrors(['error' => 'Beds24 room ID is required for this action.']);
            }

            $payload['roomId'] = (int) $data['beds24_room_id'];
        }

        try {
            $result = $client->post($account, 'channels/airbnb', [$payload]);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $this->auditLogger->log('channels.airbnb_action', 'channels', 'channel_account', (string) $account->id);

        return back()->with('status', 'Airbnb action sent to Beds24.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'refresh_token' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $account = ChannelAccount::create([
            'provider' => $data['provider'],
            'name' => $data['name'],
            'status' => $data['status'],
            'credentials' => array_filter([
                'refresh_token' => $data['refresh_token'] ?? null,
            ]),
        ]);

        $this->auditLogger->log('channels.configured', 'channels', 'channel_account', (string) $account->id);

        return back()->with('status', 'Channel account saved.');
    }

    public function edit(Request $request, ChannelAccount $account): View
    {
        return view('admin.channels.edit', [
            'account' => $account,
            ...$this->channelPageData($request),
        ]);
    }

    public function update(Request $request, ChannelAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'refresh_token' => ['nullable', 'string'],
        ]);

        $old = $account->only(['name', 'status']);

        $credentials = $account->credentials ?? [];
        if (! empty($data['refresh_token'])) {
            $credentials['refresh_token'] = $data['refresh_token'];
        }

        $account->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'credentials' => $credentials,
        ]);

        $new = $account->only(['name', 'status']);
        $this->auditLogger->log('channels.updated', 'channels', 'channel_account', (string) $account->id, $old, $new);

        return redirect()->route('admin.channels.integrations')->with('status', 'Channel account updated.');
    }

    public function destroy(ChannelAccount $account): RedirectResponse
    {
        $this->auditLogger->log('channels.deleted', 'channels', 'channel_account', (string) $account->id, newValues: ['name' => $account->name]);
        $account->delete();

        return redirect()->route('admin.channels.integrations')->with('status', 'Channel account deleted.');
    }

    public function setup(Request $request, ChannelAccount $account, Beds24AuthService $auth): RedirectResponse
    {
        $data = $request->validate([
            'invite_code' => ['required', 'string', 'max:255'],
        ]);

        try {
            $auth->exchangeInviteCode($account, $data['invite_code']);
            $this->auditLogger->log('channels.setup', 'channels', 'channel_account', (string) $account->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('status', 'Beds24 invite code exchanged. Refresh token stored encrypted.');
    }

    public function setupCredentials(Request $request, Beds24AuthService $auth): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:channel_accounts,id'],
            'invite_code' => ['required', 'string', 'max:255'],
        ]);

        $account = ChannelAccount::query()->findOrFail($data['account_id']);

        if ($account->provider !== 'beds24') {
            return back()->withErrors(['error' => 'Beds24 credentials can only be set up for Beds24 accounts.']);
        }

        try {
            $auth->exchangeInviteCode($account, $data['invite_code']);
            $this->auditLogger->log('channels.setup', 'channels', 'channel_account', (string) $account->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('status', 'Beds24 credentials set up. Refresh token stored encrypted.');
    }

    public function details(ChannelAccount $account, Beds24AuthService $auth): JsonResponse
    {
        try {
            $details = $auth->details($account);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['ok' => true, ...$details]);
    }

    public function storeMapping(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel_account_id' => ['required', 'exists:channel_accounts,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'external_property_id' => ['nullable', 'string', 'max:100'],
            'external_room_id' => ['nullable', 'string', 'max:100'],
            'external_listing_id' => ['nullable', 'string', 'max:100'],
        ]);

        $account = ChannelAccount::query()->findOrFail($data['channel_account_id']);

        ChannelMapping::create([
            ...$data,
            'provider' => $account->provider,
            'status' => 'active',
        ]);

        return back()->with('status', 'Channel mapping saved.');
    }

    public function import(Request $request, Beds24ChannelProvider $beds24): RedirectResponse
    {
        $data = $request->validate([
            'channel_account_id' => ['required', 'exists:channel_accounts,id'],
            'property_id' => ['required', 'exists:properties,id'],
        ]);

        $account = ChannelAccount::query()->findOrFail($data['channel_account_id']);

        try {
            $mappings = $beds24->importPropertyRooms($account, (int) $data['property_id']);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $this->auditLogger->log('channels.imported', 'channels', 'channel_account', (string) $account->id);

        return back()->with('status', count($mappings).' Beds24 room(s) imported. Map them to Corner House rooms.');
    }

    public function syncProperties(Request $request, Beds24SyncService $sync): RedirectResponse
    {
        $data = $request->validate([
            'channel_account_id' => ['required', 'exists:channel_accounts,id'],
        ]);

        $account = ChannelAccount::query()->findOrFail($data['channel_account_id']);

        if ($account->provider !== 'beds24') {
            return back()->withErrors(['error' => 'Only Beds24 accounts can sync properties.']);
        }

        try {
            $counts = $sync->syncCatalog($account);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $this->auditLogger->log('channels.properties_synced', 'channels', 'channel_account', (string) $account->id);

        return back()->with('status', sprintf(
            'Saved %d Beds24 propert%s and %d room%s into the system.',
            $counts['properties'],
            $counts['properties'] === 1 ? 'y' : 'ies',
            $counts['rooms'],
            $counts['rooms'] === 1 ? '' : 's',
        ));
    }

    public function syncRooms(Request $request, Beds24SyncService $sync): RedirectResponse
    {
        $data = $request->validate([
            'channel_account_id' => ['required', 'exists:channel_accounts,id'],
        ]);

        $account = ChannelAccount::query()->findOrFail($data['channel_account_id']);

        if ($account->provider !== 'beds24') {
            return back()->withErrors(['error' => 'Only Beds24 accounts can sync rooms.']);
        }

        try {
            $counts = $sync->syncRooms($account);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $this->auditLogger->log('channels.rooms_synced', 'channels', 'channel_account', (string) $account->id);

        return back()->with('status', sprintf(
            'Saved %d Beds24 room%s into the system%s.',
            $counts['rooms'],
            $counts['rooms'] === 1 ? '' : 's',
            $counts['skipped'] > 0 ? '; '.$counts['skipped'].' skipped' : '',
        ));
    }

    public function test(Request $request, ChannelAccount $account, Beds24Client $client): JsonResponse
    {
        $data = $request->validate([
            'method' => ['required', 'in:GET,POST,PATCH,DELETE,get,post,patch,delete'],
            'endpoint' => ['required', 'string', 'max:100'],
            'body' => ['nullable'],
        ]);

        $payload = $data['body'] ?? [];
        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            if (! is_array($decoded)) {
                return response()->json(['message' => 'Body must be valid JSON.'], 422);
            }
            $payload = $decoded;
        }
        if (! is_array($payload)) {
            $payload = [];
        }

        try {
            $result = $client->test($account, $data['method'], $data['endpoint'], $payload);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['ok' => true, ...$result]);
    }

    public function sync(): RedirectResponse
    {
        SyncBeds24BookingsJob::dispatch();
        $this->auditLogger->log('channels.sync', 'channels');

        return back()->with('status', 'Beds24 sync queued. Properties, rooms, bookings and calendar will be aligned.');
    }

    private function publishReservation(
        Reservation $reservation,
        Beds24BookingPublisher $publisher,
        string $successMessage,
        string $failureMessage
    ): RedirectResponse {
        if ($publisher->postBooking($reservation)) {
            return back()->with('status', $successMessage);
        }

        return back()->with('status', $failureMessage);
    }

    /**
     * @return array{
     *     accounts: Collection<int, ChannelAccount>,
     *     logs: LengthAwarePaginator,
     *     mappings: Collection<int, ChannelMapping>,
     *     properties: Collection<int, Property>,
     *     rooms: Collection<int, Room>,
     *     testEndpoints: array<int, string>,
     * }
     */
    private function channelPageData(Request $request): array
    {
        $logsQuery = ChannelSyncLog::query()->latest();

        if ($search = $request->input('log_search')) {
            $logsQuery->where(function ($q) use ($search) {
                $q->where('channel', 'like', "%{$search}%")
                    ->orWhere('operation', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('log_status')) {
            $logsQuery->where('status', $status);
        }

        return [
            'accounts' => ChannelAccount::query()->withCount('mappings')->latest()->get(),
            'logs' => $logsQuery->paginate(15)->withQueryString(),
            'mappings' => ChannelMapping::query()->with(['property', 'room', 'account'])->latest()->get(),
            'pricingRules' => PricingRule::query()->with(['property', 'room'])->orderByDesc('created_at')->limit(20)->get(),
            'pricingOverrides' => PricingOverride::query()->with(['room'])->orderByDesc('created_at')->limit(20)->get(),
            'reservations' => Reservation::query()->with(['room', 'guest'])->latest()->limit(20)->get(),
            'properties' => Property::query()->withCount('rooms')->orderBy('name')->get(),
            'rooms' => Room::query()->with('property')->orderBy('name')->get(),
            'testEndpoints' => Beds24Client::ALLOWED_TEST_ENDPOINTS,
        ];
    }

    /**
     * @return Collection<int, array{beds24_property_id: string, label: string}>
     */
    private function beds24Properties(): Collection
    {
        return ChannelMapping::query()
            ->where('provider', 'beds24')
            ->whereNotNull('external_property_id')
            ->whereNotNull('property_id')
            ->with('property')
            ->orderBy('external_property_id')
            ->get()
            ->unique('external_property_id')
            ->values()
            ->map(static function (ChannelMapping $mapping): array {
                return [
                    'beds24_property_id' => (string) $mapping->external_property_id,
                    'label' => trim(($mapping->property?->name ?? 'Property').' (Beds24 '.$mapping->external_property_id.')'),
                ];
            });
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

    /**
     * @param  Collection<int, ChannelAccount>  $accounts
     */
    private function selectedBeds24Account(Collection $accounts, mixed $accountId): ?ChannelAccount
    {
        if ($accounts->isEmpty()) {
            return null;
        }

        if ($accountId !== null && $accountId !== '') {
            $selected = $accounts->firstWhere('id', (int) $accountId);
            if ($selected instanceof ChannelAccount) {
                return $selected;
            }
        }

        return $accounts->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{airbnb_user_id: string, first_name: string, picture: ?string, raw: array<string, mixed>}>
     */
    private function extractAirbnbUsers(array $payload): array
    {
        $rows = data_get($payload, 'data', []);
        if (! is_array($rows)) {
            return [];
        }

        $users = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $airbnbUser = is_array($row['airbnbUser'] ?? null) ? $row['airbnbUser'] : $row;
            $userId = (string) data_get($airbnbUser, 'airbnbUserId', data_get($row, 'airbnbUserId', ''));
            if ($userId === '') {
                continue;
            }

            $users[] = [
                'airbnb_user_id' => $userId,
                'first_name' => (string) data_get($airbnbUser, 'firstName', 'Unknown'),
                'picture' => data_get($airbnbUser, 'picture'),
                'raw' => $row,
            ];
        }

        return $users;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{airbnb_listing_id: string, room_id: ?string, name: string, enabled: ?bool, property_type_category: ?string, room_type_category: ?string, bedrooms: ?int, bathrooms: ?int, beds: ?int, raw: array<string, mixed>}>
     */
    private function extractAirbnbListings(array $payload): array
    {
        $rows = data_get($payload, 'data', []);
        if (! is_array($rows)) {
            return [];
        }

        $listings = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $airbnbListing = is_array($row['airbnbListing'] ?? null) ? $row['airbnbListing'] : [];
            $listingId = (string) data_get($airbnbListing, 'id', '');
            if ($listingId === '') {
                continue;
            }
            $pictures = $this->extractAirbnbPictures($row, $airbnbListing);

            $listings[] = [
                'airbnb_listing_id' => $listingId,
                'room_id' => data_get($row, 'roomId') !== null ? (string) data_get($row, 'roomId') : null,
                'name' => (string) data_get($row, 'name', data_get($airbnbListing, 'name', 'Unnamed listing')),
                'enabled' => data_get($row, 'enabled'),
                'property_type_category' => data_get($airbnbListing, 'property_type_category'),
                'room_type_category' => data_get($airbnbListing, 'room_type_category'),
                'bedrooms' => data_get($airbnbListing, 'bedrooms'),
                'bathrooms' => data_get($airbnbListing, 'bathrooms'),
                'beds' => data_get($airbnbListing, 'beds'),
                'pictures' => $pictures,
                'raw' => $row,
            ];
        }

        return $listings;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractAirbnbListingDetail(array $payload): array
    {
        $rows = data_get($payload, 'data', []);
        if (! is_array($rows)) {
            return [];
        }

        $row = $rows[0] ?? [];
        if (! is_array($row)) {
            return [];
        }
        $airbnbListing = is_array(data_get($row, 'airbnbListing')) ? data_get($row, 'airbnbListing') : [];

        return [
            'room_id' => data_get($row, 'roomId') !== null ? (string) data_get($row, 'roomId') : null,
            'name' => (string) data_get($row, 'name', 'Unnamed listing'),
            'enabled' => data_get($row, 'enabled'),
            'airbnb_listing' => $airbnbListing,
            'pictures' => $this->extractAirbnbPictures($row, $airbnbListing),
            'raw' => $row,
        ];
    }

    /**
     * @param  array<string, mixed>  ...$sources
     * @return array<int, string>
     */
    private function extractAirbnbPictures(array ...$sources): array
    {
        $pictures = [];

        foreach ($sources as $source) {
            foreach (['pictures', 'photos', 'images', 'imageUrls', 'image_urls'] as $key) {
                $value = data_get($source, $key);

                if (! is_array($value)) {
                    continue;
                }

                foreach ($value as $item) {
                    if (is_string($item) && $item !== '') {
                        $pictures[] = $item;

                        continue;
                    }

                    if (! is_array($item)) {
                        continue;
                    }

                    foreach (['url', 'src', 'imageUrl', 'image_url', 'fullUrl', 'full_url', 'link'] as $field) {
                        $pictureUrl = data_get($item, $field);
                        if (is_string($pictureUrl) && $pictureUrl !== '') {
                            $pictures[] = $pictureUrl;

                            continue 2;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($pictures));
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
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractBookingReviews(array $payload): array
    {
        $rows = data_get($payload, 'data', []);
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, static fn ($row): bool => is_array($row)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractVrboBookings(array $payload): array
    {
        $rows = data_get($payload, 'data', []);
        if (! is_array($rows)) {
            return [];
        }

        $bookings = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $sourceId = (int) data_get($row, 'apiSourceId', data_get($row, 'api_source_id', 0));
            $sourceText = strtolower((string) data_get($row, 'apiSourceText', data_get($row, 'api_source_text', data_get($row, 'source', ''))));

            if ($sourceId !== 30 && ! str_contains($sourceText, 'vrbo') && ! str_contains($sourceText, 'homeaway')) {
                continue;
            }

            $bookings[] = [
                'id' => (string) data_get($row, 'id', data_get($row, 'bookingId', data_get($row, 'bookId', ''))),
                'reference' => (string) data_get($row, 'reference', data_get($row, 'ref', 'N/A')),
                'room_id' => data_get($row, 'roomId') !== null ? (string) data_get($row, 'roomId') : null,
                'guest_name' => trim(implode(' ', array_filter([
                    (string) data_get($row, 'firstName', data_get($row, 'guestFirstName', '')),
                    (string) data_get($row, 'lastName', data_get($row, 'guestLastName', '')),
                ]))),
                'guest_email' => data_get($row, 'email'),
                'check_in' => data_get($row, 'arrival', data_get($row, 'checkIn')),
                'check_out' => data_get($row, 'departure', data_get($row, 'checkOut')),
                'status' => (string) data_get($row, 'status', 'unknown'),
                'api_source_text' => (string) data_get($row, 'apiSourceText', data_get($row, 'api_source_text', 'VRBO')),
                'api_source_id' => $sourceId,
                'raw' => $row,
            ];
        }

        return $bookings;
    }
}
