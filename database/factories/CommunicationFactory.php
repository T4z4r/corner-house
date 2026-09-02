<?php

namespace Database\Factories;

use App\Models\Communication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Communication>
 */
class CommunicationFactory extends Factory
{
    protected $model = Communication::class;

    public function definition(): array
    {
        return [
            'channel' => 'email',
            'direction' => 'outbound',
            'recipient' => fake()->safeEmail(),
            'sender_name' => null,
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'status' => 'pending',
            'sent_at' => now(),
            'metadata' => null,
        ];
    }
}
