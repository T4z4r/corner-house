<?php

namespace Database\Seeders;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            ['parking', 'Is parking available?', 'Yes, off-street parking is available for guests. Please use the driveway and keep the access clear.'],
            ['check-in', 'What time is check-in?', 'Check-in is from 15:00. Early arrival can be arranged subject to availability.'],
            ['check-out', 'What time is check-out?', 'Check-out is by 10:00. Late check-out may be possible if the room is free.'],
            ['amenities', 'Is there Wi-Fi?', 'Complimentary high-speed Wi-Fi is available throughout the property.'],
            ['location', 'Where is the nearest supermarket?', 'A supermarket is a short drive from the property. Ask us on arrival for directions.'],
        ];

        foreach ($articles as [$category, $title, $content]) {
            KnowledgeBaseArticle::firstOrCreate(
                ['title' => $title],
                [
                    'category' => $category,
                    'content' => $content,
                    'status' => 'active',
                    'priority' => 1,
                ],
            );
        }
    }
}
