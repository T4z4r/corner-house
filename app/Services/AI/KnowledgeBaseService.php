<?php

namespace App\Services\AI;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Support\Collection;

class KnowledgeBaseService
{
    private const MAX_CONTENT_LENGTH = 300;

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

    /**
     * Return article content truncated for token efficiency.
     */
    public function truncatedContent(KnowledgeBaseArticle $article): string
    {
        $content = $article->content;

        if (strlen($content) <= self::MAX_CONTENT_LENGTH) {
            return $content;
        }

        return rtrim(substr($content, 0, self::MAX_CONTENT_LENGTH)).'…';
    }
}
