<?php

namespace Tests\Feature;

use App\Models\CalendarBlock;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\ChannelSyncLog;
use App\Models\Guest;
use App\Models\PricingOverride;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Services\Beds24\Beds24ChannelProvider;
use App\Services\Beds24\Beds24SyncService;
use App\Services\Booking\BookingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Schema\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Beds24IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        Setting::firstOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    public function test_invite_code_is_exchanged_for_a_refresh_token(): void
    {
        $account = ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'inactive']);

        Http::fake([
            '*authentication/setup*' => Http::response([
                'token' => 'access-token',
                'expiresIn' => 86400,
                'refreshToken' => 'refresh-token',
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.setup', $account), [
                'invite_code' => 'INVITE123',
            ])->assertRedirect();

        $account->refresh();
        $this->assertSame('active', $account->status);
        $this->assertSame('refresh-token', $account->credentials['refresh_token']);
        $this->assertSame('access-token', $account->credentials['access_token']);

        $this->assertDatabaseHas('channel_sync_logs', [
            'channel_account_id' => $account->id,
            'channel' => 'beds24',
            'operation' => 'GET authentication/setup',
            'status' => 'success',
        ]);

        $log = ChannelSyncLog::query()->latest()->first();

        $this->assertSame('[redacted]', $log?->request['headers']['code'] ?? null);
        $this->assertSame('[redacted]', $log?->response['body']['token'] ?? null);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/setup')
            && $request->hasHeader('code', 'INVITE123'));
    }

    public function test_requests_use_the_token_header(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);

        Http::fake([
            '*authentication/token*' => Http::response(['token' => 'access-1', 'expiresIn' => 86400], 200),
            '*properties*' => Http::response(['data' => []], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.channels.test', $account), [
                'method' => 'GET',
                'endpoint' => 'properties',
                'body' => '{"includeAllRooms":"true"}',
            ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseCount('channel_sync_logs', 2);
        $this->assertDatabaseHas('channel_sync_logs', [
            'channel_account_id' => $account->id,
            'channel' => 'beds24',
            'operation' => 'GET properties',
            'status' => 'success',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'properties')
            && $request->hasHeader('token', 'access-1')
            && ! $request->hasHeader('Authorization'));
    }

    public function test_accounts_endpoint_is_available_in_the_test_window(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);

        Http::fake([
            '*authentication/token*' => Http::response(['token' => 'access-2', 'expiresIn' => 86400], 200),
            '*accounts*' => Http::response(['data' => []], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.channels.test', $account), [
                'method' => 'GET',
                'endpoint' => 'accounts',
            ])->assertOk()->assertJsonPath('ok', true);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'accounts')
            && $request->hasHeader('token', 'access-2'));
    }

    public function test_beds24_credentials_setup_endpoint_exchanges_invite_code(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'inactive',
        ]);

        Http::fake([
            '*authentication/setup*' => Http::response([
                'token' => 'access-setup',
                'expiresIn' => 86400,
                'refreshToken' => 'refresh-setup',
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.credentials.setup'), [
                'account_id' => $account->id,
                'invite_code' => 'INVITE-SETUP',
            ])->assertRedirect();

        $account->refresh();
        $this->assertSame('active', $account->status);
        $this->assertSame('refresh-setup', $account->credentials['refresh_token']);
        $this->assertSame('access-setup', $account->credentials['access_token']);
    }

    public function test_channels_page_lists_documented_beds24_test_endpoints(): void
    {
        ChannelSyncLog::create([
            'channel_account_id' => null,
            'channel' => 'beds24',
            'operation' => 'GET bookings',
            'request' => [
                'method' => 'GET',
                'endpoint' => 'bookings',
                'headers' => [
                    'token' => '[redacted]',
                ],
            ],
            'response' => [
                'body' => [
                    'data' => [],
                ],
            ],
            'status' => 'success',
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.integrations'))
            ->assertOk()
            ->assertSee('Beds24 integrations')
            ->assertSee('authentication/setup')
            ->assertSee('authentication/token')
            ->assertSee('bookings/invoices')
            ->assertSee('channels/settings')
            ->assertSee('channels/airbnb')
            ->assertSee('channels/airbnb/users')
            ->assertSee('channels/airbnb/listings')
            ->assertSee('channels/airbnb/reviews')
            ->assertSee('PATCH')
            ->assertSee('DELETE')
            ->assertSee('View details')
            ->assertSee('beds24LogModal');
    }

    public function test_airbnb_page_renders_beds24_airbnb_data(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'authentication/token')) {
                return Http::response(['token' => 'access-airbnb', 'expiresIn' => 86400], 200);
            }

            if (str_contains($url, 'channels/airbnb/users')) {
                return Http::response([
                    'success' => true,
                    'type' => 'airbnbUser',
                    'data' => [[
                        'airbnbUser' => [
                            'airbnbUserId' => '123456',
                            'firstName' => 'Alex',
                            'picture' => 'https://example.com/avatar.jpg',
                        ],
                    ]],
                ], 200);
            }

            if (str_contains($url, 'channels/airbnb/listings') && str_contains($url, 'airbnbListingId=')) {
                return Http::response([
                    'success' => true,
                    'type' => 'airbnbListing',
                    'data' => [[
                        'roomId' => 77,
                        'name' => 'Oak Suite',
                        'enabled' => true,
                        'airbnbListing' => [
                            'id' => 'listing-1',
                            'name' => 'Oak Suite',
                            'property_type_category' => 'apartment',
                            'room_type_category' => 'entire_home',
                            'bedrooms' => 2,
                            'bathrooms' => 1,
                            'beds' => 2,
                            'pictures' => [
                                'https://example.com/listing-1.jpg',
                                ['url' => 'https://example.com/listing-2.jpg'],
                            ],
                            'city' => 'Towcester',
                            'country_code' => 'GB',
                        ],
                    ]],
                ], 200);
            }

            if (str_contains($url, 'channels/airbnb/listings')) {
                return Http::response([
                    'success' => true,
                    'type' => 'airbnbListing',
                    'data' => [[
                        'roomId' => 77,
                        'name' => 'Oak Suite',
                        'enabled' => true,
                        'airbnbListing' => [
                            'id' => 'listing-1',
                            'name' => 'Oak Suite',
                            'pictures' => [
                                ['src' => 'https://example.com/listing-1.jpg'],
                                ['src' => 'https://example.com/listing-2.jpg'],
                            ],
                        ],
                    ]],
                ], 200);
            }

            if (str_contains($url, 'channels/airbnb/reviews')) {
                return Http::response([
                    'success' => true,
                    'type' => 'airbnbReview',
                    'pages' => [
                        'nextPage' => null,
                        'previousPage' => null,
                        'pageCount' => 1,
                        'currentPage' => 1,
                        'pageSize' => 100,
                    ],
                    'data' => [[
                        'id' => 'review-1',
                        'public_review' => 'Lovely view and a great stay.',
                        'overall_rating' => 9,
                        'category_ratings' => [
                            ['category' => 'Cleanliness', 'comment' => 'Spotless', 'rating' => 9],
                            ['category' => 'Communication', 'comment' => '', 'rating' => 10],
                        ],
                        'first_completed_at' => '2026-07-01 10:00:00',
                        'reviewee_response' => 'Thanks for staying!',
                        'reviewer_id' => 'guest-1',
                        'submitted' => true,
                    ]],
                ], 200);
            }

            return Http::response([], 404);
        });

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.airbnb', [
                'account_id' => $account->id,
                'airbnb_user_id' => '123456',
                'airbnb_listing_id' => 'listing-1',
            ]))
            ->assertOk()
            ->assertSee('Airbnb via Beds24')
            ->assertSee('How this page works:')
            ->assertSee('Send to Beds24')
            ->assertSee('Airbnb users')
            ->assertSee('Alex')
            ->assertSee('Airbnb listings')
            ->assertSee('Oak Suite')
            ->assertSee('Airbnb reviews')
            ->assertSee('Lovely view')
            ->assertSee('Selected listing details')
            ->assertSee('https://example.com/listing-1.jpg')
            ->assertSee('https://example.com/listing-2.jpg')
            ->assertSee('href="'.route('admin.channels.airbnb').'" class="nav-link active"', false)
            ->assertDontSee('href="'.route('admin.channels.integrations').'" class="nav-link active"', false)
            ->assertSee('const detailLoaded = true;', false)
            ->assertSee('const listingSelected = true;', false);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'channels/airbnb/users')
            && $request->hasHeader('token', 'access-airbnb'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'channels/airbnb/listings')
            && str_contains($request->url(), 'airbnbUserId=123456'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'channels/airbnb/listings')
            && str_contains($request->url(), 'airbnbListingId=listing-1'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'channels/airbnb/reviews')
            && str_contains($request->url(), 'roomId=77'));
    }

    public function test_booking_page_renders_beds24_booking_data(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);
        $room = Room::factory()->create();

        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'external_property_id' => '55001',
            'external_room_id' => '441',
            'status' => 'active',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'authentication/token')) {
                return Http::response(['token' => 'access-booking', 'expiresIn' => 86400], 200);
            }

            if (str_contains($url, 'channels/booking/reviews')) {
                return Http::response([
                    'success' => true,
                    'type' => 'bookingReview',
                    'data' => [[
                        'review_id' => 'booking-review-1',
                        'content' => [
                            'headline' => 'Great location',
                            'positive' => 'Clean and quiet.',
                            'negative' => '',
                        ],
                        'reviewer' => [
                            'name' => 'Mia',
                        ],
                        'scoring' => [
                            'review_score' => 8.8,
                        ],
                    ]],
                ], 200);
            }

            return Http::response([], 404);
        });

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.booking', [
                'account_id' => $account->id,
                'room_id' => '441',
            ]))
            ->assertOk()
            ->assertSee('Booking.com via Beds24')
            ->assertSee('Booking.com reviews')
            ->assertSee('Great location')
            ->assertSee('Mia')
            ->assertSee('Selected room details')
            ->assertSee('href="'.route('admin.channels.booking').'" class="nav-link active"', false)
            ->assertDontSee('href="'.route('admin.channels.airbnb').'" class="nav-link active"', false);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'channels/booking/reviews')
            && str_contains($request->url(), 'roomId=441')
            && $request->hasHeader('token', 'access-booking'));
    }

    public function test_vrbo_page_renders_beds24_vrbo_bookings(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);
        $room = Room::factory()->create();

        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'external_property_id' => '55001',
            'external_room_id' => '441',
            'status' => 'active',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'authentication/token')) {
                return Http::response(['token' => 'access-vrbo', 'expiresIn' => 86400], 200);
            }

            if (str_contains($url, 'bookings')) {
                return Http::response([
                    'success' => true,
                    'type' => 'booking',
                    'data' => [[
                        'id' => 90101,
                        'reference' => 'VRBO-9001',
                        'roomId' => 441,
                        'firstName' => 'Rita',
                        'lastName' => 'Mason',
                        'email' => 'rita@example.com',
                        'arrival' => '2026-09-01',
                        'departure' => '2026-09-04',
                        'status' => 'confirmed',
                        'apiSourceId' => 30,
                        'apiSourceText' => 'Homeaway XML',
                    ]],
                ], 200);
            }

            return Http::response([], 404);
        });

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.vrbo', [
                'account_id' => $account->id,
                'booking_id' => '90101',
            ]))
            ->assertOk()
            ->assertSee('VRBO via Beds24')
            ->assertSee('Vrbo bookings')
            ->assertSee('VRBO-9001')
            ->assertSee('Rita Mason')
            ->assertSee('Selected booking details')
            ->assertSee('href="'.route('admin.channels.vrbo').'" class="nav-link active"', false)
            ->assertDontSee('href="'.route('admin.channels.booking').'" class="nav-link active"', false);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bookings')
            && str_contains($request->url(), 'apiSourceId=30')
            && $request->hasHeader('token', 'access-vrbo'));
    }

    public function test_airbnb_action_can_import_a_listing_as_a_new_property_in_beds24(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);

        Http::fake([
            '*authentication/token*' => Http::response(['token' => 'access-action', 'expiresIn' => 86400], 200),
            '*channels/airbnb*' => Http::response([
                'success' => true,
                'type' => 'multiplePostResponse',
                'data' => [],
            ], 201),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.airbnb.actions'), [
                'account_id' => $account->id,
                'action' => 'importAsNewProperty',
                'airbnb_user_id' => '123456',
                'airbnb_listing_id' => 'listing-1',
                'connect' => 'full',
                'import_blocked_dates' => true,
                'import_bookings' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Airbnb action sent to Beds24.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'channels/airbnb')
            && $request->hasHeader('token', 'access-action')
            && ($request->data()[0]['action'] ?? null) === 'importAsNewProperty'
            && ($request->data()[0]['airbnbUserId'] ?? null) === '123456'
            && ($request->data()[0]['airbnbListingId'] ?? null) === 'listing-1'
            && ($request->data()[0]['connect'] ?? null) === 'full'
            && ($request->data()[0]['importBlockedDates'] ?? null) === true
            && ($request->data()[0]['importBookings'] ?? null) === true);
    }

    public function test_beds24_properties_can_be_saved_into_the_system(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);

        Http::fake([
            '*authentication/token*' => Http::response(['token' => 'access-sync', 'expiresIn' => 86400], 200),
            '*properties*' => Http::response([
                'success' => true,
                'type' => 'property',
                'data' => [[
                    'id' => 350650,
                    'name' => 'Corner House',
                    'address' => '2 Banbury Close',
                    'city' => 'Northampton',
                    'country' => 'GB',
                    'postcode' => 'NN4 9UA',
                    'currency' => 'EUR',
                ]],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.properties.sync'), [
                'channel_account_id' => $account->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Saved 1 Beds24 property and 0 rooms into the system.');

        $this->assertDatabaseHas('properties', [
            'name' => 'Corner House',
            'address_line_1' => '2 Banbury Close',
            'city' => 'Northampton',
            'postcode' => 'NN4 9UA',
            'country' => 'GB',
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('channel_mappings', [
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'external_property_id' => '350650',
            'external_room_id' => null,
            'status' => 'active',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'properties')
            && $request->hasHeader('token', 'access-sync')
            && str_contains($request->url(), 'includeAllRooms=true'));
    }

    public function test_beds24_rooms_can_be_saved_into_the_system(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);
        $property = Property::factory()->create([
            'name' => 'Corner House',
            'slug' => 'corner-house',
            'status' => 'active',
        ]);

        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'external_property_id' => '350650',
            'status' => 'active',
            'metadata' => ['beds24_property_name' => 'Corner House'],
        ]);

        Http::fake([
            '*authentication/token*' => Http::response(['token' => 'access-rooms', 'expiresIn' => 86400], 200),
            '*properties/rooms*' => Http::response([
                'success' => true,
                'type' => 'room',
                'data' => [[
                    'id' => 9001,
                    'propertyId' => 350650,
                    'name' => 'Garden Room',
                    'roomType' => 'double',
                    'qty' => 1,
                    'maxPeople' => 2,
                    'minPrice' => 85,
                    'rackRate' => 95,
                    'minStay' => 2,
                    'texts' => [[
                        'language' => 'en',
                        'displayName' => 'Garden Room',
                        'roomDescription' => 'Garden facing room.',
                    ]],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.rooms.sync'), [
                'channel_account_id' => $account->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Saved 1 Beds24 room into the system.');

        $this->assertDatabaseHas('rooms', [
            'property_id' => $property->id,
            'name' => 'Garden Room',
            'type' => 'double',
            'capacity' => 2,
            'sleeps' => 2,
            'min_stay' => 2,
        ]);

        $room = Room::query()->where('name', 'Garden Room')->firstOrFail();

        $this->assertSame('85.00', (string) $room->base_rate);

        $this->assertDatabaseHas('channel_mappings', [
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'external_room_id' => '9001',
            'external_property_id' => '350650',
            'room_id' => $room->id,
            'property_id' => $property->id,
            'status' => 'active',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'properties/rooms')
            && $request->hasHeader('token', 'access-rooms')
            && str_contains($request->url(), 'propertyId')
            && str_contains($request->url(), 'includeLanguages')
            && str_contains($request->url(), 'includeTexts')
            && str_contains($request->url(), 'includeUnitDetails'));
    }

    public function test_beds24_room_sync_falls_back_to_properties_when_rooms_endpoint_fails(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);
        $property = Property::factory()->create([
            'name' => 'Corner House',
            'slug' => 'corner-house',
            'status' => 'active',
        ]);

        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'external_property_id' => '350650',
            'status' => 'active',
            'metadata' => ['beds24_property_name' => 'Corner House'],
        ]);

        Http::fake([
            '*authentication/token*' => Http::response(['token' => 'access-fallback', 'expiresIn' => 86400], 200),
            '*properties/rooms*' => Http::response(['success' => false, 'code' => 500, 'error' => 'Server error'], 500),
            '*properties*' => Http::response([
                'success' => true,
                'type' => 'property',
                'data' => [[
                    'id' => 350650,
                    'name' => 'Corner House',
                    'roomTypes' => [[
                        'id' => 9002,
                        'name' => 'Loft Room',
                        'roomType' => 'single',
                        'qty' => 1,
                        'maxPeople' => 1,
                        'minPrice' => 90,
                        'minStay' => 1,
                    ]],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.rooms.sync'), [
                'channel_account_id' => $account->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Saved 1 Beds24 room into the system.');

        $this->assertDatabaseHas('rooms', [
            'property_id' => $property->id,
            'name' => 'Loft Room',
            'type' => 'single',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'properties/rooms')
            && $request->hasHeader('token', 'access-fallback'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'properties')
            && $request->hasHeader('token', 'access-fallback'));
    }

    public function test_setup_page_renders_beds24_credentials_form(): void
    {
        ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.setup.page'))
            ->assertOk()
            ->assertSee('Beds24 setup')
            ->assertSee('Beds24 credentials setup')
            ->assertSee('Exchange code')
            ->assertSee('href="'.route('admin.channels.setup.page').'" class="nav-link active"', false)
            ->assertDontSee('href="'.route('admin.channels.integrations').'" class="nav-link active"', false);
    }

    public function test_integrations_page_marks_only_integrations_link_active(): void
    {
        ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.integrations'))
            ->assertOk()
            ->assertSee('Beds24 integrations')
            ->assertSee('href="'.route('admin.channels.integrations').'" class="nav-link active"', false)
            ->assertDontSee('href="'.route('admin.channels.setup.page').'" class="nav-link active"', false);
    }

    public function test_authentication_setup_endpoint_uses_code_header(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
        ]);

        Http::fake([
            '*authentication/setup*' => Http::response([
                'token' => 'access-setup',
                'expiresIn' => 86400,
                'refreshToken' => 'refresh-setup',
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.channels.test', $account), [
                'method' => 'GET',
                'endpoint' => 'authentication/setup',
                'body' => '{"code":"INVITE999"}',
            ])->assertOk()->assertJsonPath('ok', true);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/setup')
            && $request->hasHeader('code', 'INVITE999')
            && ! $request->hasHeader('token'));
    }

    public function test_test_window_rejects_unknown_endpoints(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => ['refresh_token' => 'refresh-token'],
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.channels.test', $account), [
                'method' => 'GET',
                'endpoint' => 'not-a-real-endpoint',
            ])->assertStatus(422);
    }

    public function test_import_creates_room_mappings(): void
    {
        $property = Property::factory()->create();
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        Http::fake([
            '*properties*' => Http::response([
                'data' => [[
                    'id' => 1001,
                    'name' => 'Corner House',
                    'roomTypes' => [
                        ['id' => 55, 'name' => 'Garden Room'],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.import'), [
                'channel_account_id' => $account->id,
                'property_id' => $property->id,
            ])->assertRedirect();

        $this->assertDatabaseHas('channel_mappings', [
            'channel_account_id' => $account->id,
            'property_id' => $property->id,
            'external_property_id' => '1001',
            'external_room_id' => '55',
        ]);
    }

    public function test_availability_push_uses_calendar_payload(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        Http::fake([
            '*inventory/rooms/calendar*' => Http::response(['success' => true], 200),
        ]);

        app(Beds24ChannelProvider::class)->pushAvailability($account, [[
            'roomId' => 55,
            'from' => '2026-09-01',
            'to' => '2026-09-03',
            'numAvail' => 0,
        ]]);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), 'inventory/rooms/calendar')
                && ($body[0]['roomId'] ?? null) === 55
                && ($body[0]['calendar'][0]['numAvail'] ?? null) === 0;
        });
    }

    public function test_authentication_details_returns_scopes_and_diagnostics(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        Http::fake([
            '*authentication/details*' => Http::response([
                'validToken' => true,
                'token' => [
                    'ownerId' => 42,
                    'expiresIn' => 86000,
                    'scopes' => ['read:bookings', 'write:inventory'],
                    'deviceName' => 'Corner House',
                ],
                'diagnostics' => [
                    'requestIp' => '192.168.0.1',
                ],
            ], 200, [
                'X-FiveMinCreditLimit' => '100',
                'X-FiveMinCreditLimit-Remaining' => '88',
                'X-FiveMinCreditLimit-ResetsIn' => '200',
                'X-RequestCost' => '1',
            ]),
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.channels.details', $account))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('valid', true)
            ->assertJsonPath('token.scopes.0', 'read:bookings')
            ->assertJsonPath('diagnostics.requestIp', '192.168.0.1')
            ->assertJsonPath('credits.remaining', '88');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/details')
            && $request->hasHeader('token', 'access-1'));

        $account->refresh();
        $this->assertTrue($account->settings['token_valid']);
        $this->assertSame(['read:bookings', 'write:inventory'], $account->settings['scopes']);
    }

    public function test_authentication_details_returns_422_when_beds24_reports_an_error(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        Http::fake([
            '*authentication/details*' => Http::response([
                'success' => false,
                'type' => 'error',
                'code' => 400,
                'error' => 'Invalid token',
            ], 400),
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.channels.details', $account))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid token');
    }

    public function test_full_sync_creates_local_property_room_booking_and_calendar(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(23)->toDateString();
        $closed = now()->addDays(5)->toDateString();

        Http::fake([
            '*properties*' => Http::response([
                'data' => [[
                    'id' => 2001,
                    'name' => 'Corner House B24',
                    'city' => 'Towcester',
                    'postcode' => 'NN12 1AA',
                    'country' => 'GB',
                    'currency' => 'GBP',
                    'roomTypes' => [[
                        'id' => 77,
                        'name' => 'Oak Suite',
                        'maxPeople' => 3,
                        'minStay' => 2,
                    ]],
                ]],
            ], 200),
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 9001,
                    'propertyId' => 2001,
                    'roomId' => 77,
                    'arrival' => $checkIn,
                    'departure' => $checkOut,
                    'firstName' => 'Lee',
                    'lastName' => 'Guest',
                    'email' => 'lee@example.com',
                    'numAdult' => 2,
                    'status' => 'confirmed',
                    'channel' => 'airbnb',
                    'price' => 360,
                ]],
            ], 200),
            '*inventory/rooms/calendar*' => Http::response([
                'data' => [[
                    'roomId' => 77,
                    'calendar' => [
                        ['date' => $closed, 'numAvail' => 0, 'price1' => 150, 'minStay' => 2],
                    ],
                ]],
            ], 200),
        ]);

        $counts = app(Beds24SyncService::class)->synchronize($account);

        $this->assertSame(1, $counts['properties']);
        $this->assertSame(1, $counts['rooms']);
        $this->assertSame(1, $counts['bookings']);
        $this->assertDatabaseHas('properties', ['name' => 'Corner House B24', 'city' => 'Towcester']);
        $this->assertDatabaseHas('rooms', ['name' => 'Oak Suite', 'capacity' => 3, 'min_stay' => 2]);
        $this->assertDatabaseHas('reservations', [
            'external_channel' => 'beds24',
            'external_booking_id' => '9001',
            'source' => 'airbnb',
            'status' => 'confirmed',
        ]);
        $this->assertTrue(
            CalendarBlock::query()
                ->where('type', 'channel')
                ->whereDate('start_date', $closed)
                ->whereDate('end_date', $closed)
                ->exists()
        );
        $this->assertDatabaseHas('pricing_overrides', [
            'notes' => 'beds24-sync',
            'rate' => 150,
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'inventory/rooms/calendar')
            && str_contains($request->url(), 'numAvail')
            && str_contains($request->url(), 'minStay')
            && str_contains($request->url(), 'maxStay')
            && str_contains($request->url(), 'price1'));
    }

    public function test_full_sync_replaces_overlapping_channel_blocks(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'Oak Suite',
            'status' => 'active',
        ]);

        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        $closed = now()->addDays(5)->toDateString();

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'type' => 'channel',
            'title' => 'Old Beds24 block',
            'notes' => 'beds24-sync',
        ]);

        Http::fake([
            '*properties*' => Http::response(['data' => []], 200),
            '*bookings*' => Http::response(['data' => []], 200),
            '*inventory/rooms/calendar*' => Http::response([
                'data' => [[
                    'roomId' => 77,
                    'calendar' => [
                        ['date' => $closed, 'available' => 0, 'price' => 150, 'minimumStay' => 2],
                    ],
                ]],
            ], 200),
        ]);

        app(Beds24SyncService::class)->synchronize($account);

        $this->assertDatabaseMissing('calendar_blocks', [
            'title' => 'Old Beds24 block',
        ]);

        $this->assertDatabaseHas('calendar_blocks', [
            'property_id' => $property->id,
            'room_id' => $room->id,
            'type' => 'channel',
            'title' => 'Beds24 closed',
        ]);
    }

    public function test_pricing_rule_can_be_posted_to_beds24_when_requested(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'base_rate' => 100,
        ]);
        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        Http::fake([
            '*inventory/rooms/calendar*' => Http::response(['success' => true], 200),
        ]);

        $start = now()->addDays(12)->toDateString();
        $end = now()->addDays(14)->toDateString();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.pricing.rules.store'), [
                'room_id' => $room->id,
                'name' => 'Summer high season',
                'rule_type' => 'seasonal',
                'start_date' => $start,
                'end_date' => $end,
                'priority' => 4,
                'adjustment_type' => 'amount',
                'adjustment_value' => 10,
                'minimum_stay' => 2,
                'max_stay' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Pricing rule created.');
    }

    public function test_pricing_override_can_be_posted_to_beds24_when_requested(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'base_rate' => 100,
        ]);
        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        Http::fake([
            '*inventory/rooms/calendar*' => Http::response(['success' => true], 200),
        ]);

        $start = now()->addDays(16)->toDateString();
        $end = now()->addDays(18)->toDateString();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.pricing.overrides.store'), [
                'room_id' => $room->id,
                'start_date' => $start,
                'end_date' => $end,
                'rate' => 125,
                'minimum_stay' => 2,
                'notes' => 'Weekend uplift',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Rate override created.');
    }

    public function test_integrations_page_lists_pricing_publish_actions(): void
    {
        $room = Room::factory()->create();

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Summer high season',
            'rule_type' => 'seasonal',
            'priority' => 4,
            'adjustment_type' => 'amount',
            'adjustment_value' => 10,
            'is_enabled' => true,
        ]);

        PricingOverride::create([
            'room_id' => $room->id,
            'start_date' => now()->addDays(16)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'rate' => 125,
            'minimum_stay' => 2,
            'notes' => 'Weekend uplift',
            'is_enabled' => true,
            'created_by' => $this->superAdmin()->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.integrations'))
            ->assertOk()
            ->assertSee('Pricing publishing')
            ->assertSee('Publish')
            ->assertSee('Summer high season')
            ->assertSee($room->name)
            ->assertSee('£125.00');
    }

    public function test_integrations_page_lists_property_publish_actions(): void
    {
        $property = Property::factory()->create(['city' => 'Northampton']);
        Room::factory()->count(2)->create(['property_id' => $property->id]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.integrations'))
            ->assertOk()
            ->assertSee('Property publishing')
            ->assertSee('Publish property')
            ->assertSee($property->name)
            ->assertSee('2 rooms');
    }

    public function test_property_can_be_published_from_integrations_page(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create([
            'name' => 'Corner House',
            'city' => 'Northampton',
            'postcode' => 'NN4 9UA',
            'country' => 'GB',
            'currency' => 'GBP',
        ]);
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'name' => 'Oak Suite',
        ]);

        Http::fake([
            '*properties*' => Http::response([
                'data' => [[
                    'id' => 5001,
                    'roomTypes' => [[
                        'id' => 9001,
                        'name' => 'Oak Suite',
                    ]],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.properties.publish', $property))
            ->assertRedirect()
            ->assertSessionHas('status', 'Property posted to Beds24.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'properties')
            && ($request->data()[0]['name'] ?? null) === 'Corner House'
            && ($request->data()[0]['roomTypes'][0]['name'] ?? null) === 'Oak Suite');

        $this->assertDatabaseHas('channel_mappings', [
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => null,
            'external_property_id' => '5001',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('channel_mappings', [
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_room_id' => '9001',
            'status' => 'active',
        ]);
    }

    public function test_integrations_page_lists_booking_publish_actions(): void
    {
        $guest = Guest::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Taylor',
            'email' => 'alex@example.com',
        ]);
        $reservation = Reservation::factory()->create([
            'guest_id' => $guest->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.channels.integrations'))
            ->assertOk()
            ->assertSee('Bookings publishing')
            ->assertSee('Publish booking')
            ->assertSee('Post guests')
            ->assertSee($reservation->reference)
            ->assertSee('Alex Taylor');
    }

    public function test_schema_default_string_length_is_capped_for_mysql_indexes(): void
    {
        $reflection = new \ReflectionClass(Builder::class);
        $property = $reflection->getProperty('defaultStringLength');

        $this->assertSame(191, $property->getValue());
    }

    public function test_booking_can_be_published_from_integrations_page(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);
        $guest = Guest::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Taylor',
            'email' => 'alex@example.com',
            'phone' => '+447700900123',
        ]);
        $reservation = Reservation::factory()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'guests_count' => 2,
            'status' => 'confirmed',
            'source' => 'direct',
            'channel' => 'website',
            'external_channel' => null,
            'external_booking_id' => null,
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'first_name' => 'Jordan',
            'last_name' => 'Taylor',
            'email' => 'jordan@example.com',
            'phone' => '+447700900124',
            'type' => 'adult',
        ]);

        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        Http::fake([
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 7001,
                ]],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.bookings.publish', $reservation))
            ->assertRedirect()
            ->assertSessionHas('status', 'Booking posted to Beds24.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bookings')
            && (int) ($request->data()[0]['roomId'] ?? 0) === 77
            && (string) ($request->data()[0]['firstName'] ?? '') === 'Alex'
            && (string) ($request->data()[0]['lastName'] ?? '') === 'Taylor'
            && (string) ($request->data()[0]['email'] ?? '') === 'alex@example.com'
            && (string) ($request->data()[0]['phone'] ?? '') === '+447700900123'
            && ($request->data()[0]['guests'][0]['firstName'] ?? null) === 'Alex'
            && ($request->data()[0]['guests'][1]['firstName'] ?? null) === 'Jordan'
            && ($request->data()[0]['guests'][1]['email'] ?? null) === 'jordan@example.com');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'external_channel' => 'beds24',
            'external_booking_id' => '7001',
            'sync_status' => 'synced',
        ]);
    }

    public function test_guest_details_can_be_posted_from_integrations_page(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);
        $guest = Guest::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Taylor',
            'email' => 'alex@example.com',
            'phone' => '+447700900123',
        ]);
        $reservation = Reservation::factory()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'guests_count' => 2,
            'status' => 'confirmed',
            'source' => 'direct',
            'channel' => 'website',
            'external_channel' => null,
            'external_booking_id' => null,
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'first_name' => 'Jordan',
            'last_name' => 'Taylor',
            'email' => 'jordan@example.com',
            'phone' => '+447700900124',
            'type' => 'adult',
        ]);

        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        Http::fake([
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 7002,
                ]],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.bookings.guests.publish', $reservation))
            ->assertRedirect()
            ->assertSessionHas('status', 'Guest details posted to Beds24.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bookings')
            && (int) ($request->data()[0]['roomId'] ?? 0) === 77
            && (string) ($request->data()[0]['firstName'] ?? '') === 'Alex'
            && (string) ($request->data()[0]['lastName'] ?? '') === 'Taylor'
            && (string) ($request->data()[0]['email'] ?? '') === 'alex@example.com'
            && (string) ($request->data()[0]['phone'] ?? '') === '+447700900123'
            && ($request->data()[0]['guests'][0]['firstName'] ?? null) === 'Alex'
            && ($request->data()[0]['guests'][1]['firstName'] ?? null) === 'Jordan'
            && ($request->data()[0]['guests'][1]['email'] ?? null) === 'jordan@example.com');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'external_channel' => 'beds24',
            'external_booking_id' => '7002',
            'sync_status' => 'synced',
        ]);
    }

    public function test_prices_can_be_imported_from_beds24_into_local_overrides(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'base_rate' => 0,
        ]);

        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        $priceDate = now()->addDays(8)->toDateString();

        Http::fake([
            '*inventory/rooms/calendar*' => Http::response([
                'data' => [[
                    'roomId' => 77,
                    'calendar' => [[
                        'date' => $priceDate,
                        'numAvail' => 0,
                        'price1' => 145,
                        'minStay' => 3,
                        'maxStay' => 6,
                    ]],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.prices.import'), [
                'account_id' => $account->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Imported 1 price override and 1 calendar block from Beds24.');

        $this->assertTrue(
            PricingOverride::query()
                ->where('room_id', $room->id)
                ->whereDate('start_date', $priceDate)
                ->whereDate('end_date', $priceDate)
                ->where('rate', 145)
                ->where('minimum_stay', 3)
                ->where('notes', 'beds24-sync')
                ->where('is_enabled', true)
                ->exists()
        );

        $this->assertDatabaseHas('calendar_blocks', [
            'room_id' => $room->id,
            'property_id' => $property->id,
            'type' => 'channel',
            'title' => 'Beds24 closed',
        ]);
    }

    public function test_pricing_rule_can_be_published_from_integrations_page(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'base_rate' => 100,
        ]);
        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);
        $rule = PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Summer high season',
            'rule_type' => 'seasonal',
            'start_date' => now()->addDays(12)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'priority' => 4,
            'adjustment_type' => 'amount',
            'adjustment_value' => 10,
            'minimum_stay' => 2,
            'max_stay' => 5,
            'is_enabled' => true,
        ]);

        Http::fake([
            '*inventory/rooms/calendar*' => Http::response(['success' => true], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.pricing.rules.publish', $rule))
            ->assertRedirect()
            ->assertSessionHas('status', 'Pricing rule posted to Beds24.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'inventory/rooms/calendar')
            && (int) ($request->data()[0]['roomId'] ?? 0) === 77
            && (float) ($request->data()[0]['calendar'][0]['price1'] ?? 0) === 110.0);
    }

    public function test_pricing_override_can_be_published_from_integrations_page(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'base_rate' => 100,
        ]);
        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);
        $override = PricingOverride::create([
            'room_id' => $room->id,
            'start_date' => now()->addDays(16)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'rate' => 125,
            'minimum_stay' => 2,
            'notes' => 'Weekend uplift',
            'is_enabled' => true,
            'created_by' => $this->superAdmin()->id,
        ]);

        Http::fake([
            '*inventory/rooms/calendar*' => Http::response(['success' => true], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.pricing.overrides.publish', $override))
            ->assertRedirect()
            ->assertSessionHas('status', 'Rate override posted to Beds24.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'inventory/rooms/calendar')
            && (int) ($request->data()[0]['roomId'] ?? 0) === 77
            && (float) ($request->data()[0]['calendar'][0]['price1'] ?? 0) === 125.0);
    }

    public function test_sync_updates_existing_beds24_booking_dates(): void
    {
        $room = Room::factory()->create(['status' => 'active']);
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $guest = Guest::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Guest',
            'email' => 'old@example.com',
            'phone' => '+447700900000',
        ]);
        ChannelMapping::factory()->create([
            'channel_account_id' => $account->id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'provider' => 'beds24',
            'external_room_id' => '77',
        ]);

        $reservation = Reservation::factory()->create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'status' => 'confirmed',
            'external_channel' => 'beds24',
            'external_booking_id' => '9001',
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
        ]);

        $newIn = now()->addDays(14)->toDateString();
        $newOut = now()->addDays(17)->toDateString();

        Http::fake([
            '*properties*' => Http::response(['data' => []], 200),
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 9001,
                    'roomId' => 77,
                    'arrival' => $newIn,
                    'departure' => $newOut,
                    'status' => 'confirmed',
                    'numAdult' => 2,
                    'firstName' => 'Jamie',
                    'lastName' => 'Channel',
                    'email' => 'jamie@example.com',
                    'phone' => '+447700900111',
                ]],
            ], 200),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        app(Beds24SyncService::class)->synchronize($account);

        $reservation->refresh();
        $reservation->guest?->refresh();
        $this->assertSame($newIn, $reservation->check_in->toDateString());
        $this->assertSame($newOut, $reservation->check_out->toDateString());
        $this->assertSame('synced', $reservation->sync_status);
        $this->assertSame('Jamie', $reservation->guest?->first_name);
        $this->assertSame('Channel', $reservation->guest?->last_name);
        $this->assertSame('jamie@example.com', $reservation->guest?->email);
        $this->assertSame('+447700900111', $reservation->guest?->phone);
    }

    public function test_confirmed_local_booking_is_pushed_to_beds24_and_cancelled_bookings_sync_back(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);
        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        Http::fake([
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 7003,
                ]],
            ], 200),
            '*inventory/rooms/calendar*' => Http::response(['success' => true], 200),
        ]);

        $reservation = app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => now()->addDays(21)->toDateString(),
            'check_out' => now()->addDays(24)->toDateString(),
            'guests_count' => 2,
            'guest_email' => 'alex@example.com',
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Taylor',
            'status' => 'confirmed',
            'source' => 'direct',
        ])['reservation'];

        $reservation->refresh();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bookings')
            && (int) ($request->data()[0]['roomId'] ?? 0) === 77
            && (string) ($request->data()[0]['firstName'] ?? '') === 'Alex'
            && (string) ($request->data()[0]['status'] ?? '') === 'confirmed');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'external_channel' => 'beds24',
            'external_booking_id' => '7003',
            'sync_status' => 'synced',
        ]);

        $reservation->refresh();

        Http::fake([
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 7003,
                ]],
            ], 200),
            '*inventory/rooms/calendar*' => Http::response(['success' => true], 200),
        ]);

        app(BookingService::class)->cancel($reservation, 'Guest requested cancellation');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bookings')
            && (int) ($request->data()[0]['id'] ?? 0) === 7003
            && (string) ($request->data()[0]['status'] ?? '') === 'cancelled');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
        ]);
    }
}
