<?php

namespace Mindwave\Mindwave\Context\Sources;

use Mindwave\Mindwave\Context\ContextCollection;
use Mindwave\Mindwave\Context\ContextItem;
use Mindwave\Mindwave\Context\Contracts\ContextSource;

/**
 * Static Context Source
 *
 * Provides hardcoded context strings, useful for FAQs, documentation snippets,
 * or any static information that should always be available.
 */
class StaticSource implements ContextSource
{
    /** @var array<int, array{content: string, keywords: array<string>, metadata: array<string, mixed>}> */
    private array $items = [];

    private bool $initialized = false;

    public function __construct(
        private string $name = 'static'
    ) {}

    /**
     * Create from array of strings.
     *
     * @param  array<string>  $strings  Array of content strings
     * @param  string  $name  Source name
     */
    public static function fromStrings(array $strings, string $name = 'static-strings'): self
    {
        $instance = new self($name);

        foreach ($strings as $index => $content) {
            $instance->items[] = [
                'content' => $content,
                'keywords' => self::extractKeywords($content),
                'metadata' => ['index' => $index],
            ];
        }

        return $instance;
    }

    /**
     * Create from structured items with keywords.
     *
     * @param  array<array{content: string, keywords?: array<string>, metadata?: array<string, mixed>}>  $items
     * @param  string  $name  Source name
     */
    public static function fromItems(array $items, string $name = 'static-items'): self
    {
        $instance = new self($name);

        foreach ($items as $index => $item) {
            $instance->items[] = [
                'content' => $item['content'],
                'keywords' => $item['keywords'] ?? self::extractKeywords($item['content']),
                'metadata' => $item['metadata'] ?? ['index' => $index],
            ];
        }

        return $instance;
    }

    public function initialize(): void
    {
        $this->initialized = true;
    }

    public function search(string $query, int $limit = 5): ContextCollection
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $queryKeywords = self::extractKeywords($query);
        $results = [];

        foreach ($this->items as $item) {
            $score = $this->calculateScore($queryKeywords, $item['keywords'], $item['content'], $query);

            if ($score > 0) {
                $results[] = [
                    'item' => $item,
                    'score' => $score,
                ];
            }
        }

        // Sort by score descending
        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Limit results
        $results = array_slice($results, 0, $limit);

        // Convert to ContextItems
        $items = array_map(
            fn ($result) => ContextItem::make(
                content: $result['item']['content'],
                score: $result['score'],
                source: $this->name,
                metadata: $result['item']['metadata']
            ),
            $results
        );

        return new ContextCollection($items);
    }

    public function cleanup(): void
    {
        $this->initialized = false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Extract keywords from text.
     *
     * @return array<string>
     */
    private static function extractKeywords(string $text): array
    {
        // Convert to lowercase and split on non-alphanumeric characters
        $words = preg_split('/[^a-z0-9]+/i', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'what', 'which', 'who', 'when', 'where', 'why', 'how'];

        $keywords = array_filter($words, fn ($word) => strlen($word) > 2 && ! in_array($word, $stopWords));

        return array_values(array_unique($keywords));
    }

    /**
     * Calculate relevance score.
     *
     * @param  array<string>  $queryKeywords
     * @param  array<string>  $itemKeywords
     */
    private function calculateScore(array $queryKeywords, array $itemKeywords, string $content, string $query): float
    {
        if (empty($queryKeywords)) {
            return 0.0;
        }

        // Exact phrase match bonus
        if (stripos($content, $query) !== false) {
            return 1.0;
        }

        // Keyword matching
        $matches = array_intersect($queryKeywords, $itemKeywords);
        $matchCount = count($matches);

        if ($matchCount === 0) {
            return 0.0;
        }

        // Score based on ratio of matched keywords
        $score = $matchCount / count($queryKeywords);

        // Bonus for matching more keywords
        $score *= (1 + ($matchCount * 0.1));

        // Normalize to 0-1 range
        return min(1.0, $score);
    }
}
