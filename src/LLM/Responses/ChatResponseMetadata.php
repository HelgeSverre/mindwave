<?php

namespace Mindwave\Mindwave\LLM\Responses;

/**
 * Chat Response Metadata
 *
 * Contains metadata collected from a streaming chat response.
 * This is returned after the stream has been fully consumed.
 */
readonly class ChatResponseMetadata
{
    public function __construct(
        public ?string $role = null,
        public ?string $finishReason = null,
        public ?string $model = null,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?int $totalTokens = null,
        public string $content = '',
        public array $toolCalls = [],
    ) {}
}
