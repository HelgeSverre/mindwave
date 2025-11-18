<?php

namespace Mindwave\Mindwave\Context\Sources;

use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Context\ContextCollection;
use Mindwave\Mindwave\Context\ContextItem;
use Mindwave\Mindwave\Context\Contracts\ContextSource;

/**
 * Vector Store Context Source
 *
 * Semantic similarity search using Mindwave's Brain (vector store).
 * Best for finding conceptually similar content rather than exact keyword matches.
 */
class VectorStoreSource implements ContextSource
{
    private bool $initialized = false;

    public function __construct(
        private Brain $brain,
        private string $name = 'vectorstore'
    ) {}

    /**
     * Create from a Brain instance.
     */
    public static function fromBrain(Brain $brain, string $name = 'vectorstore'): self
    {
        return new self($brain, $name);
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

        // Use Brain's semantic search
        $results = $this->brain->search($query, $limit);

        $items = array_map(
            fn ($result) => ContextItem::make(
                content: $result['content'] ?? '',
                score: (float) ($result['score'] ?? $result['distance'] ?? 0.0),
                source: $this->name,
                metadata: $result['metadata'] ?? []
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
     * Get the underlying Brain instance.
     */
    public function getBrain(): Brain
    {
        return $this->brain;
    }
}
