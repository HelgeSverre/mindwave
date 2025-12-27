<?php

namespace Mindwave\Mindwave\LLM\Responses;

use Generator;

/**
 * Streamed Chat Response
 *
 * Represents a streaming chat completion response that yields chunks of text
 * along with metadata as they arrive from the LLM provider.
 *
 * Unlike StreamedTextResponse which only yields raw text chunks, this class
 * provides structured data including role, finish reason, and token usage.
 *
 * Features:
 * - Yields text deltas as they arrive
 * - Tracks metadata (role, finish reason, model)
 * - Accumulates token usage information
 * - Supports tool calls in streaming mode
 * - Provides completion callback for final metadata
 *
 * Usage:
 * ```php
 * $stream = $llm->streamChat($messages);
 * $response = new StreamedChatResponse($stream);
 *
 * foreach ($response->chunks() as $chunk) {
 *     echo $chunk->content;
 * }
 *
 * // Get final metadata after streaming completes
 * $metadata = $response->getMetadata();
 * echo "Used {$metadata->totalTokens} tokens";
 * ```
 */
class StreamedChatResponse
{
    private Generator $stream;

    private ?string $role = null;

    private ?string $finishReason = null;

    private ?string $model = null;

    private ?int $inputTokens = null;

    private ?int $outputTokens = null;

    private string $accumulatedContent = '';

    private array $toolCalls = [];

    private bool $consumed = false;

    /**
     * @param  Generator<StreamChunk>  $stream  The stream of chat chunks
     */
    public function __construct(Generator $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Get an iterator over the stream chunks.
     *
     * Each chunk contains incremental content and metadata updates.
     *
     * @return Generator<StreamChunk> Yields chunks with content and metadata
     */
    public function chunks(): Generator
    {
        if ($this->consumed) {
            throw new \RuntimeException('Stream has already been consumed');
        }

        $this->consumed = true;

        foreach ($this->stream as $chunk) {
            // Update metadata from chunk
            if ($chunk->role !== null) {
                $this->role = $chunk->role;
            }

            if ($chunk->finishReason !== null) {
                $this->finishReason = $chunk->finishReason;
            }

            if ($chunk->model !== null) {
                $this->model = $chunk->model;
            }

            if ($chunk->inputTokens !== null) {
                $this->inputTokens = $chunk->inputTokens;
            }

            if ($chunk->outputTokens !== null) {
                $this->outputTokens = $chunk->outputTokens;
            }

            // Accumulate content
            if ($chunk->content !== null && $chunk->content !== '') {
                $this->accumulatedContent .= $chunk->content;
            }

            // Track tool calls
            if ($chunk->toolCalls !== null && count($chunk->toolCalls) > 0) {
                $this->toolCalls = array_merge($this->toolCalls, $chunk->toolCalls);
            }

            yield $chunk;
        }
    }

    /**
     * Get the complete accumulated text from the stream.
     *
     * Note: This consumes the stream if not already consumed.
     *
     * @return string The complete text content
     */
    public function getText(): string
    {
        if (! $this->consumed) {
            // Consume the stream to accumulate content
            foreach ($this->chunks() as $chunk) {
                // Just iterate to accumulate
            }
        }

        return $this->accumulatedContent;
    }

    /**
     * Get the final metadata after streaming completes.
     *
     * Note: This consumes the stream if not already consumed.
     *
     * @return ChatResponseMetadata The final metadata
     */
    public function getMetadata(): ChatResponseMetadata
    {
        if (! $this->consumed) {
            // Consume the stream to collect metadata
            foreach ($this->chunks() as $chunk) {
                // Just iterate to collect metadata
            }
        }

        return new ChatResponseMetadata(
            role: $this->role,
            finishReason: $this->finishReason,
            model: $this->model,
            inputTokens: $this->inputTokens,
            outputTokens: $this->outputTokens,
            totalTokens: $this->inputTokens !== null && $this->outputTokens !== null
                ? $this->inputTokens + $this->outputTokens
                : null,
            content: $this->accumulatedContent,
            toolCalls: $this->toolCalls,
        );
    }

    /**
     * Convert the streamed response to a complete ChatResponse.
     *
     * This consumes the entire stream and returns a standard ChatResponse object.
     *
     * @return ChatResponse The complete response
     */
    public function toChatResponse(): ChatResponse
    {
        $metadata = $this->getMetadata();

        return new ChatResponse(
            content: $metadata->content,
            role: $metadata->role,
            inputTokens: $metadata->inputTokens,
            outputTokens: $metadata->outputTokens,
            finishReason: $metadata->finishReason,
            model: $metadata->model,
            raw: ['toolCalls' => $metadata->toolCalls],
        );
    }

    /**
     * Check if the stream has been consumed.
     */
    public function isConsumed(): bool
    {
        return $this->consumed;
    }
}
