<?php

namespace Mindwave\Mindwave\LLM\Responses;

readonly class ChatResponse
{
    public function __construct(
        public string $content,
        public ?string $role = null,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?string $finishReason = null,
        public ?string $model = null,
        public array $raw = [],
    ) {
    }
}
