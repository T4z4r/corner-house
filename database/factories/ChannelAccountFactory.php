<?php

namespace Database\Factories;

use App\Models\ChannelAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelAccount>
 */
class ChannelAccountFactory extends Factory
{
    protected $model = ChannelAccount::class;

    public function definition(): array
    {
        return [
            'provider' => 'beds24',
            'name' => 'Beds24',
            'status' => 'inactive',
            'credentials' => [],
            'settings' => [],
        ];
    }
}
