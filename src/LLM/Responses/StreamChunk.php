<?php

namespace Mindwave\Mindwave\LLM\Responses;

/**
 * Stream Chunk
 *
 * Represents a single chunk of data from a streaming LLM response.
 * Each chunk may contain incremental content and/or metadata updates.
 *
 * Properties may be null if not present in this particular chunk.
 * For example, the first chunk might contain the role, while subsequent
 * chunks only contain content deltas.
 */
readonly class StreamChunk
{
    public function __construct(
        public ?string $content = null,
        public ?string $role = null,
        public ?string $finishReason = null,
        public ?string $model = null,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?array $toolCalls = null,
        public array $raw = [],
    ) {}

    /**
     * Check if this chunk contains any content.
     */
    public function hasContent(): bool
    {
        return $this->content !== null && $this->content !== '';
    }

    /**
     * Check if this chunk marks the end of the stream.
     */
    public function isComplete(): bool
    {
        return $this->finishReason !== null;
    }

    /**
     * Check if this chunk contains tool call information.
     */
    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== null && count($this->toolCalls) > 0;
    }
}
