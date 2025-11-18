<?php

namespace Mindwave\Mindwave\Context\Sources;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mindwave\Mindwave\Context\ContextCollection;
use Mindwave\Mindwave\Context\ContextItem;
use Mindwave\Mindwave\Context\Contracts\ContextSource;

/**
 * Eloquent Context Source
 *
 * Simple SQL LIKE-based search for Eloquent models. Fast for small datasets
 * but less sophisticated than TNTSearch or vector search.
 */
class EloquentSource implements ContextSource
{
    private Builder $query;

    /** @var array<string> */
    private array $searchColumns;

    private Closure $transformer;

    private bool $initialized = false;

    /**
     * @param  Builder  $query  Eloquent query builder
     * @param  array<string>  $searchColumns  Columns to search (uses SQL LIKE)
     * @param  Closure  $transformer  Function to transform model to string
     * @param  string  $name  Source name
     */
    public function __construct(
        Builder $query,
        array $searchColumns,
        Closure $transformer,
        private string $name = 'eloquent'
    ) {
        $this->query = clone $query;
        $this->searchColumns = $searchColumns;
        $this->transformer = $transformer;
    }

    /**
     * Create an Eloquent source.
     *
     * @param  Builder  $query  Eloquent query builder
     * @param  array<string>  $searchColumns  Columns to search with LIKE
     * @param  Closure  $transformer  Transform model to string (e.g., fn($user) => $user->name)
     * @param  string  $name  Source identifier
     */
    public static function create(
        Builder $query,
        array $searchColumns,
        Closure $transformer,
        string $name = 'eloquent'
    ): self {
        return new self($query, $searchColumns, $transformer, $name);
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

        // Build WHERE LIKE clauses for each search column
        $searchQuery = clone $this->query;

        $searchQuery->where(function ($q) use ($query) {
            foreach ($this->searchColumns as $index => $column) {
                if ($index === 0) {
                    $q->where($column, 'LIKE', "%{$query}%");
                } else {
                    $q->orWhere($column, 'LIKE', "%{$query}%");
                }
            }
        });

        $models = $searchQuery->limit($limit)->get();

        $items = $models->map(function (Model $model) use ($query) {
            $content = ($this->transformer)($model);

            // Calculate simple relevance score
            $score = $this->calculateScore($content, $query);

            return ContextItem::make(
                content: $content,
                score: $score,
                source: $this->name,
                metadata: [
                    'model_id' => $model->getKey(),
                    'model_type' => get_class($model),
                ]
            );
        })->toArray();

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
     * Calculate relevance score based on query term frequency.
     */
    private function calculateScore(string $content, string $query): float
    {
        $contentLower = strtolower($content);
        $queryLower = strtolower($query);

        // Exact match gets highest score
        if (stripos($content, $query) !== false) {
            return 1.0;
        }

        // Count matches of individual words
        $queryWords = preg_split('/\s+/', $queryLower, -1, PREG_SPLIT_NO_EMPTY);
        $matches = 0;

        foreach ($queryWords as $word) {
            if (strlen($word) > 2 && stripos($contentLower, $word) !== false) {
                $matches++;
            }
        }

        if ($matches === 0) {
            return 0.3; // Low score for LIKE match without word matches
        }

        // Score based on percentage of query words found
        return min(1.0, 0.5 + ($matches / count($queryWords)) * 0.5);
    }
}
