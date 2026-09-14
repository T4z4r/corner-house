<?php

namespace Tests\Feature;

use App\Models\CalendarBlock;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\ChannelPricingSnapshot;
use App\Models\PricingOverride;
use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Beds24ShowDataImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        Setting::updateOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    private function showDataBody(string $beds24RoomId, array $rows): string
    {
        $lines = [
            'Booking.com',
            $beds24RoomId.' - Corner House',
            '',
            'Price Multiplier = 1',
            "Date\tInventory\tRate code\tClosed\tPrice\tMin Stay",
            ...$rows,
            '',
            "Date\tInventory\tRate code\tClosed\tPrice\tMin Stay",
        ];

        return implode("\r\n", $lines);
    }

    private function setupMappedRoom(): array
    {
        $account = ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);
        $property = Property::factory()->create(['status' => 'active']);
        $room = Room::factory()->create(['property_id' => $property->id, 'status' => 'active', 'base_rate' => 100]);

        ChannelMapping::factory()->create([
            'channel_account_id' => $account->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'provider' => 'beds24',
            'external_room_id' => '724804',
            'status' => 'active',
        ]);

        return [$account, $room];
    }

    public function test_import_creates_channel_blocks_for_closed_nights(): void
    {
        [$account, $room] = $this->setupMappedRoom();

        $closedStart = Carbon::today()->addDays(10);
        $next = $closedStart->copy()->addDay();

        Http::preventStrayRequests();
        Http::fake([
            '*showdata.php*' => Http::response($this->showDataBody('724804', [
                $closedStart->format('D j M Y')."\t0\t1720816101\tclosed\t\t",
                $next->format('D j M Y')."\t0\t1720816101\tclosed\t\t",
                $next->copy()->addDay()->format('D j M Y')."\t1\t1720816101\t\t175.00\t3",
            ])),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.import'), [
                'account_id' => $account->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('calendar_blocks', [
            'room_id' => $room->id,
            'type' => 'channel',
            'title' => 'Beds24 closed',
            'start_date' => $closedStart->startOfDay()->toDateTimeString(),
            'end_date' => $next->startOfDay()->toDateTimeString(),
            'is_active' => true,
        ]);

        $this->assertSame(1, CalendarBlock::query()
            ->where('room_id', $room->id)
            ->where('type', 'channel')
            ->count());
    }

    public function test_open_nights_do_not_create_blocks(): void
    {
        [$account, $room] = $this->setupMappedRoom();

        $openDate = Carbon::today()->addDays(10);

        Http::preventStrayRequests();
        Http::fake([
            '*showdata.php*' => Http::response($this->showDataBody('724804', [
                $openDate->format('D j M Y')."\t1\t1720816101\t\t175.00\t3",
            ])),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.import'), [
                'account_id' => $account->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(0, CalendarBlock::query()
            ->where('room_id', $room->id)
            ->where('type', 'channel')
            ->count());
    }

    public function test_import_without_mapping_can_target_a_room_directly(): void
    {
        $account = ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);
        $property = Property::factory()->create(['status' => 'active']);
        $room = Room::factory()->create(['property_id' => $property->id, 'status' => 'active', 'base_rate' => 100]);

        $closedStart = Carbon::today()->addDays(10);
        $next = $closedStart->copy()->addDay();

        Http::preventStrayRequests();
        Http::fake([
            '*showdata.php*' => Http::response($this->showDataBody('724804', [
                $closedStart->format('D j M Y')."\t0\t1720816101\tclosed\t\t",
                $next->format('D j M Y')."\t0\t1720816101\tclosed\t\t",
            ])),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.import'), [
                'account_id' => $account->id,
                'room_id' => $room->id,
                'beds24_room_id' => '724804',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('calendar_blocks', [
            'room_id' => $room->id,
            'type' => 'channel',
            'start_date' => $closedStart->startOfDay()->toDateTimeString(),
            'end_date' => $next->startOfDay()->toDateTimeString(),
            'is_active' => true,
        ]);
    }

    public function test_imported_blocks_show_on_website_availability(): void
    {
        [$account, $room] = $this->setupMappedRoom();

        $closedStart = Carbon::today()->addDays(10);
        $next = $closedStart->copy()->addDay();

        Http::preventStrayRequests();
        Http::fake([
            '*showdata.php*' => Http::response($this->showDataBody('724804', [
                $closedStart->format('D j M Y')."\t0\t1720816101\tclosed\t\t",
                $next->format('D j M Y')."\t0\t1720816101\tclosed\t\t",
            ])),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.import'), [
                'account_id' => $account->id,
            ])
            ->assertRedirect();

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertJson([
                ['start' => $closedStart->toDateString(), 'end' => $next->copy()->addDay()->toDateString()],
            ]);
    }

    public function test_reimport_replace_old_channel_block_rows(): void
    {
        [$account, $room] = $this->setupMappedRoom();

        $oldStart = Carbon::today()->addDays(5);
        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'start_date' => $oldStart->toDateString(),
            'end_date' => $oldStart->toDateString(),
            'type' => 'channel',
            'title' => 'Beds24 closed',
            'notes' => 'beds24-showdata',
            'is_active' => true,
        ]);

        $closedStart = Carbon::today()->addDays(10);

        Http::preventStrayRequests();
        Http::fake([
            '*showdata.php*' => Http::response($this->showDataBody('724804', [
                $closedStart->format('D j M Y')."\t0\t1720816101\tclosed\t\t",
            ])),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.import'), [
                'account_id' => $account->id,
            ])
            ->assertRedirect();

        $this->assertSame(1, CalendarBlock::query()
            ->where('room_id', $room->id)
            ->where('type', 'channel')
            ->where('notes', 'beds24-showdata')
            ->count());

        $this->assertDatabaseHas('calendar_blocks', [
            'room_id' => $room->id,
            'type' => 'channel',
            'start_date' => $closedStart->startOfDay()->toDateTimeString(),
        ]);
    }

    public function test_permission_gate_for_import_action(): void
    {
        $account = ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);

        $user = User::factory()->create();
        $role = Role::findByName('Finance Manager');
        $user->assignRole($role);

        Http::preventStrayRequests();

        $this->actingAs($user)
            ->post(route('admin.channels.showdata.import'), [
                'account_id' => $account->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, CalendarBlock::query()->count());
    }

    public function test_non_beds24_account_is_rejected(): void
    {
        $account = ChannelAccount::factory()->create(['provider' => 'airbnb', 'status' => 'active']);

        Http::preventStrayRequests();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.import'), [
                'account_id' => $account->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');
    }

    public function test_paste_stores_raw_feed_and_parsed_rows(): void
    {
        [$account, $room] = $this->setupMappedRoom();

        $open = Carbon::parse('2026-09-13');
        $closedStart = Carbon::parse('2026-09-18');
        $closedNext = Carbon::parse('2026-09-19');

        $raw = implode("\r\n", [
            'Booking.com',
            '726384 - Corner House - Large country house next to marina',
            'Price Multiplier = 1',
            "Date\tInventory\tRate code\tClosed\tPrice\tMin Stay",
            $open->format('D j M Y')."\t1\t69033222\t\t550.00\t2",
            $closedStart->format('D j M Y')."\t0\t69033222\tclosed\t\t",
            $closedNext->format('D j M Y')."\t0\t69033222\tclosed\t\t",
            '',
            "Date\tInventory\tRate code\tClosed\tPrice\tMin Stay",
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.paste'), [
                'account_id' => $account->id,
                'room_id' => $room->id,
                'beds24_room_id' => '726384',
                'showdata' => $raw,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertStringContainsString('1 open / 2 closed nights', (string) session('status'));

        $snapshot = ChannelPricingSnapshot::query()->firstOrFail();
        $this->assertSame('726384', $snapshot->external_room_id);
        $this->assertSame('69033222', $snapshot->rate_code);
        $this->assertSame($room->id, $snapshot->room_id);
        $this->assertSame($raw, $snapshot->raw_data);
        $this->assertSame('2026-09-13', $snapshot->date_from->toDateString());
        $this->assertSame('2026-09-19', $snapshot->date_to->toDateString());
        $this->assertSame(1, $snapshot->open_days);
        $this->assertSame(2, $snapshot->closed_days);
        $this->assertCount(3, $snapshot->rows);

        $this->assertEquals('550.00', number_format((float) $snapshot->rows[0]['price'], 2));
        $this->assertSame(2, $snapshot->rows[0]['min_stay']);
        $this->assertFalse($snapshot->rows[0]['closed']);
        $this->assertSame(1, $snapshot->rows[0]['inventory']);
        $this->assertTrue($snapshot->rows[2]['closed']);
        $this->assertNull($snapshot->rows[2]['price']);

        $this->assertSame(0, CalendarBlock::query()->count());
        $this->assertSame(0, PricingOverride::query()->count());
    }

    public function test_paste_rejects_text_without_pricing_rows(): void
    {
        $account = ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.paste'), [
                'account_id' => $account->id,
                'beds24_room_id' => '726384',
                'showdata' => "This is some random text\nIt is not a showdata feed.",
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertStringContainsString('No pricing rows', (string) session('status'));
        $this->assertStringContainsString('No pricing rows', (string) $account->refresh()->last_error);
        $this->assertSame(0, ChannelPricingSnapshot::query()->count());
    }

    public function test_paste_requires_channels_configure_permission(): void
    {
        $account = ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);

        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Finance Manager'));

        $this->actingAs($user)
            ->post(route('admin.channels.showdata.paste'), [
                'account_id' => $account->id,
                'beds24_room_id' => '726384',
                'showdata' => $this->showDataBody('726384', [
                    Carbon::today()->addDays(10)->format('D j M Y')."\t1\t69033222\t\t550.00\t2",
                ]),
            ])
            ->assertForbidden();

        $this->assertSame(0, ChannelPricingSnapshot::query()->count());
    }

    public function test_paste_rejects_non_beds24_account(): void
    {
        $account = ChannelAccount::factory()->create(['provider' => 'airbnb', 'status' => 'active']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.showdata.paste'), [
                'account_id' => $account->id,
                'beds24_room_id' => '726384',
                'showdata' => $this->showDataBody('726384', [
                    Carbon::today()->addDays(10)->format('D j M Y')."\t1\t69033222\t\t550.00\t2",
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertSame(0, ChannelPricingSnapshot::query()->count());
    }
}
