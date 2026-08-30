<?php

namespace Database\Factories;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CommunicationTemplate>
 */
class CommunicationTemplateFactory extends Factory
{
    protected $model = CommunicationTemplate::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'event' => 'booking_confirmation',
            'channel' => 'email',
            'subject' => 'Your booking {{reference}}',
            'body' => 'Hello {{guest_name}}, your stay {{check_in}} to {{check_out}} is confirmed.',
            'is_active' => true,
        ];
    }
}
