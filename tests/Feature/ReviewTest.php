<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    public function test_super_admin_can_list_reviews(): void
    {
        Review::factory()->approved()->create(['quote' => 'Wonderful weekend.', 'cite' => 'Sam, June 2026']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Wonderful weekend.')
            ->assertSee('Sam, June 2026');
    }

    public function test_user_without_reviews_permission_cannot_list_reviews(): void
    {
        $role = Role::create(['name' => 'No Reviews Access', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view');
        $user = User::factory()->create()->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.reviews.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_a_review(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.reviews.store'), [
                'stars' => 5,
                'quote' => 'A perfect family getaway.',
                'cite' => 'The Jones family, July 2026',
                'status' => 'approved',
                'sort_order' => 1,
            ])->assertRedirect(route('admin.reviews.index'))
            ->assertSessionHas('status', 'Review created.');

        $this->assertDatabaseHas('reviews', [
            'quote' => 'A perfect family getaway.',
            'cite' => 'The Jones family, July 2026',
            'stars' => 5,
            'status' => 'approved',
            'source' => 'manual',
        ]);
    }

    public function test_creating_a_review_requires_a_quote_and_stars(): void
    {
        $this->actingAs($this->superAdmin())
            ->from(route('admin.reviews.create'))
            ->post(route('admin.reviews.store'), [
                'stars' => '',
                'quote' => '',
                'cite' => '',
            ])->assertRedirect(route('admin.reviews.create'))
            ->assertSessionHasErrors(['stars', 'quote']);
    }

    public function test_super_admin_can_toggle_review_between_approved_and_hidden(): void
    {
        $review = Review::factory()->hidden()->create(['quote' => 'Toggled review.']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.reviews.toggle', $review))
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'approved']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.reviews.toggle', $review->fresh()))
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'hidden']);
    }

    public function test_super_admin_can_delete_a_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.reviews.destroy', $review))
            ->assertRedirect(route('admin.reviews.index'))
            ->assertSessionHas('status', 'Review deleted.');

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_super_admin_can_import_airbnb_reviews_as_hidden_and_dedupe(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        Http::fake([
            '*channels/airbnb/reviews*' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 'review-1',
                        'public_review' => 'Lovely view and a great stay.',
                        'overall_rating' => 9,
                        'first_completed_at' => '2026-07-01 10:00:00',
                        'reviewer_id' => 'guest-1',
                    ],
                    [
                        'id' => 'review-2',
                        'public_review' => 'Superb hosts and spotless rooms.',
                        'overall_rating' => 10,
                        'first_completed_at' => '2026-06-15 09:00:00',
                        'reviewer_id' => 'guest-2',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.reviews.import'), [
                'account_id' => $account->id,
                'beds24_room_id' => '1234',
            ])->assertRedirect()
            ->assertSessionHas('status', 'Imported 2 Airbnb reviews as hidden. Approve them to show on the website.');

        $this->assertDatabaseHas('reviews', [
            'source' => 'airbnb',
            'source_id' => 'review-1',
            'status' => 'hidden',
            'stars' => 5,
            'quote' => 'Lovely view and a great stay.',
            'cite' => 'July 2026',
        ]);
        $this->assertDatabaseHas('reviews', [
            'source' => 'airbnb',
            'source_id' => 'review-2',
            'status' => 'hidden',
            'stars' => 5,
            'quote' => 'Superb hosts and spotless rooms.',
            'cite' => 'June 2026',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'channels/airbnb/reviews')
            && str_contains($request->url(), 'roomId=1234')
            && $request->hasHeader('token', 'access-1'));

        $this->actingAs($this->superAdmin())
            ->post(route('admin.reviews.import'), [
                'account_id' => $account->id,
                'beds24_room_id' => '1234',
            ])->assertRedirect()
            ->assertSessionHas('status', 'No new Airbnb reviews to import — everything is already in the table.');

        $this->assertDatabaseCount('reviews', 2);
    }

    public function test_import_from_airbnb_rejects_a_non_beds24_account(): void
    {
        $account = ChannelAccount::factory()->create(['provider' => 'other']);

        $this->actingAs($this->superAdmin())
            ->from(route('admin.reviews.index'))
            ->post(route('admin.reviews.import'), [
                'account_id' => $account->id,
                'beds24_room_id' => '1234',
            ])->assertRedirect(route('admin.reviews.index'))
            ->assertSessionHasErrors('error');
    }
}
