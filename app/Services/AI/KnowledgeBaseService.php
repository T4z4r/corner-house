<?php

namespace App\Services\AI;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Support\Collection;

class KnowledgeBaseService
{
    /**
     * @return Collection<int, KnowledgeBaseArticle>
     */
    public function search(string $query, int $limit = 5): Collection
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9\s]+/i', ' ', $query));
        $terms = collect(preg_split('/\s+/', $normalized) ?: [])
            ->filter(fn (string $term): bool => strlen($term) > 2)
            ->values();

        $articles = KnowledgeBaseArticle::query()
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->get();

        if ($terms->isEmpty()) {
            return $articles->take($limit);
        }

        return $articles
            ->map(function (KnowledgeBaseArticle $article) use ($terms): array {
                $haystack = strtolower($article->title.' '.$article->category.' '.$article->content);
                $score = $terms->sum(fn (string $term): int => substr_count($haystack, $term));

                return ['article' => $article, 'score' => $score];
            })
            ->filter(fn (array $row): bool => $row['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('article')
            ->values();
    }
}
