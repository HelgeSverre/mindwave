<?php

namespace Mindwave\Mindwave\Context;

use Mindwave\Mindwave\Context\Contracts\ContextSource;

/**
 * Context Pipeline
 *
 * Aggregates results from multiple context sources, deduplicates, and re-ranks them.
 * Allows combining different search strategies (TNTSearch, vector search, SQL LIKE, static).
 */
class ContextPipeline
{
    /** @var array<ContextSource> */
    private array $sources = [];

    private bool $deduplicate = true;

    private bool $rerank = true;

    /**
     * Add a context source to the pipeline.
     */
    public function addSource(ContextSource $source): self
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * Add multiple sources at once.
     *
     * @param  array<ContextSource>  $sources
     */
    public function addSources(array $sources): self
    {
        foreach ($sources as $source) {
            $this->addSource($source);
        }

        return $this;
    }

    /**
     * Enable or disable deduplication.
     */
    public function deduplicate(bool $deduplicate = true): self
    {
        $this->deduplicate = $deduplicate;

        return $this;
    }

    /**
     * Enable or disable re-ranking.
     */
    public function rerank(bool $rerank = true): self
    {
        $this->rerank = $rerank;

        return $this;
    }

    /**
     * Search across all sources.
     *
     * @param  string  $query  Search query
     * @param  int  $limit  Maximum total results to return
     */
    public function search(string $query, int $limit = 10): ContextCollection
    {
        if (empty($this->sources)) {
            return new ContextCollection([]);
        }

        // Initialize all sources
        foreach ($this->sources as $source) {
            $source->initialize();
        }

        // Search each source (requesting more to account for deduplication)
        $perSourceLimit = (int) ceil($limit * 1.5);
        $allResults = [];

        foreach ($this->sources as $source) {
            $results = $source->search($query, $perSourceLimit);
            $allResults = array_merge($allResults, $results->all());
        }

        // Create collection
        $collection = new ContextCollection($allResults);

        // Apply deduplication if enabled
        if ($this->deduplicate) {
            $collection = $collection->deduplicate();
        }

        // Apply re-ranking if enabled
        if ($this->rerank) {
            $collection = $collection->rerank();
        }

        // Apply limit
        $collection = $collection->take($limit);

        return $collection;
    }

    /**
     * Get all registered sources.
     *
     * @return array<ContextSource>
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * Clean up all sources.
     */
    public function cleanup(): void
    {
        foreach ($this->sources as $source) {
            $source->cleanup();
        }
    }
}
