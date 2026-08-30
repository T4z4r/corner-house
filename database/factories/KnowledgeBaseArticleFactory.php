<?php

namespace Database\Factories;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeBaseArticle>
 */
class KnowledgeBaseArticleFactory extends Factory
{
    protected $model = KnowledgeBaseArticle::class;

    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(['parking', 'amenities', 'check-in', 'location', 'faqs']),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'status' => 'active',
            'priority' => 0,
        ];
    }
}
