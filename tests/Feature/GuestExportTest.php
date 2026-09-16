<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function signIn(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('guests.view');
        $this->actingAs($user);
    }

    public function test_csv_exports_all_matching_guests_beyond_pagination(): void
    {
        $this->signIn();
        Guest::factory()->count(16)->create(['last_name' => 'ExportMatch']);
        $guest = Guest::factory()->create(['first_name' => 'Zoe', 'last_name' => 'ExportMatch', 'email' => 'zoe@example.com']);
        Reservation::factory()->count(2)->create(['guest_id' => $guest->id]);
        Guest::factory()->create(['last_name' => 'Excluded', 'email' => 'excluded@example.com', 'first_name' => 'Other', 'phone' => '123']);

        $response = $this->get(route('admin.guests.export', ['search' => 'ExportMatch', 'page' => 2]))->assertOk();
        $response->assertDownload('guests-'.now()->format('Y-m-d-H-i').'.csv');
        $rows = array_map('str_getcsv', explode("\n", trim($response->streamedContent())));
        $this->assertCount(18, $rows);
        $this->assertSame(['Name', 'Email', 'Phone', 'Bookings', 'Source', 'Status'], $rows[0]);
        $zoe = collect($rows)->first(fn (array $row): bool => $row[1] === 'zoe@example.com');
        $this->assertSame('2', $zoe[3]);
        $this->assertNotContains('excluded@example.com', array_column($rows, 1));
    }

    public function test_csv_preserves_quoted_fields_and_neutralizes_formulas(): void
    {
        $this->signIn();
        Guest::factory()->create(['first_name' => '=SUM(1,2)', 'last_name' => 'Guest', 'source' => 'A "quoted", source', 'phone' => '+447700900123']);
        $response = $this->get(route('admin.guests.export'))->assertOk();
        $rows = array_map('str_getcsv', explode("\n", trim($response->streamedContent())));
        $this->assertSame("'=SUM(1,2) Guest", $rows[1][0]);
        $this->assertSame("'+447700900123", $rows[1][2]);
        $this->assertSame('A "quoted", source', $rows[1][4]);
    }

    public function test_printable_export_filters_and_escapes_guest_data(): void
    {
        $this->signIn();
        Guest::factory()->create(['first_name' => '<script>alert(1)</script>', 'last_name' => 'PrintMatch']);
        Guest::factory()->create(['first_name' => 'Hidden', 'last_name' => 'Other', 'email' => 'hidden@example.com', 'phone' => '123']);

        $this->get(route('admin.guests.export', ['format' => 'html', 'search' => 'PrintMatch']))
            ->assertOk()->assertSee('Print / Save as PDF')->assertSee('PrintMatch')
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('hidden@example.com');
        $this->get(route('admin.guests.index', ['search' => 'PrintMatch']))
            ->assertSee(route('admin.guests.export', ['search' => 'PrintMatch', 'format' => 'csv']))
            ->assertSee(route('admin.guests.export', ['search' => 'PrintMatch', 'format' => 'html']));
    }

    public function test_empty_exports_and_invalid_format(): void
    {
        $this->signIn();
        $this->get(route('admin.guests.export', ['format' => 'html']))->assertOk()->assertSee('No guests found');
        $response = $this->get(route('admin.guests.export'))->assertOk();
        $this->assertSame("Name,Email,Phone,Bookings,Source,Status\n", $response->streamedContent());
        $this->getJson(route('admin.guests.export', ['format' => 'exe']))->assertUnprocessable();
    }

    public function test_exports_require_guest_view_permission(): void
    {
        $this->get(route('admin.guests.export'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        foreach (['csv', 'html'] as $format) {
            $this->get(route('admin.guests.export', ['format' => $format]))->assertForbidden();
        }

    }
}
