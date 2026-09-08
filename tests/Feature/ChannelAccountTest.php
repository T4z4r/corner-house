<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_status_can_be_saved(): void
    {
        $account = ChannelAccount::factory()->create(['status' => 'partial']);

        $this->assertSame('partial', $account->fresh()->status);
    }

    public function test_last_error_caps_overlong_exception_chains(): void
    {
        $account = ChannelAccount::factory()->create();

        $account->update(['last_error' => str_repeat('x', 10_000)]);

        $this->assertSame(5000, mb_strlen($account->fresh()->last_error));
    }

    public function test_last_error_can_be_cleared(): void
    {
        $account = ChannelAccount::factory()->create(['last_error' => 'something failed']);

        $account->update(['last_error' => null]);

        $this->assertNull($account->fresh()->last_error);
    }
}
