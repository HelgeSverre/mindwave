<?php

namespace Mindwave\Mindwave\Context;

/**
 * Context Item Value Object
 *
 * Immutable value object representing a single piece of context.
 */
readonly class ContextItem
{
    /**
     * @param  string  $content  The actual text content
     * @param  float  $score  Relevance score (0.0 = not relevant, 1.0 = highly relevant)
     * @param  string  $source  Source identifier (e.g., 'tntsearch', 'vectorstore')
     * @param  array<string, mixed>  $metadata  Additional metadata (model_id, model_type, etc.)
     */
    public function __construct(
        public string $content,
        public float $score,
        public string $source,
        public array $metadata = [],
    ) {}

    /**
     * Create a new ContextItem.
     */
    public static function make(
        string $content,
        float $score = 1.0,
        string $source = 'unknown',
        array $metadata = []
    ): self {
        return new self($content, $score, $source, $metadata);
    }

    /**
     * Create a new instance with modified score.
     */
    public function withScore(float $score): self
    {
        return new self($this->content, $score, $this->source, $this->metadata);
    }

    /**
     * Create a new instance with additional metadata.
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->content,
            $this->score,
            $this->source,
            array_merge($this->metadata, $metadata)
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'score' => $this->score,
            'source' => $this->source,
            'metadata' => $this->metadata,
        ];
    }
}
