<?php

namespace Mindwave\Mindwave\LLM\Drivers\Anthropic;

use Anthropic\Contracts\ClientContract;
use Anthropic\Responses\Messages\CreateResponse;
use Anthropic\Responses\Messages\CreateStreamedResponse;
use Anthropic\Responses\Messages\StreamResponse;
use Generator;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\Drivers\BaseDriver;

class AnthropicDriver extends BaseDriver implements LLM
{
    public function __construct(
        protected ClientContract $client,
        protected string $model = ModelNames::CLAUDE_3_5_SONNET,
        protected ?string $systemMessage = null,
        protected int $maxTokens = 4096,
        protected float $temperature = 1.0,
    ) {
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function generateText(string $prompt): ?string
    {
        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        return $response->content;
    }


    public function chat(array $messages, array $options = []): \Mindwave\Mindwave\LLM\Responses\ChatResponse
    {
        $params = array_merge([
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'messages' => $messages,
        ], $options);

        // System message is a separate parameter in Anthropic API
        if ($this->systemMessage) {
            $params['system'] = $this->systemMessage;
        }

        $response = $this->client->messages()->create($params);

        // Extract text content
        $content = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $content .= $block->text;
            }
        }

        return new \Mindwave\Mindwave\LLM\Responses\ChatResponse(
            content: $content,
            role: $response->role,
            inputTokens: $response->usage->inputTokens,
            outputTokens: $response->usage->outputTokens,
            finishReason: $response->stop_reason,
            model: $response->model,
            raw: (array) $response->toArray(),
        );
    }

    /**
     * Generate text with streaming support.
     *
     * @param  string  $prompt  The prompt to send to the LLM
     * @return Generator<string> Yields text deltas as they arrive
     */
    public function streamText(string $prompt): Generator
    {
        $stream = $this->streamChat($prompt);

        foreach ($stream as $chunk) {
            $content = $this->extractStreamedContent($chunk);
            if ($content !== '') {
                yield $content;
            }
        }
    }

    /**
     * Create a streaming chat completion request.
     *
     * @param  string  $prompt  The prompt to send
     * @return StreamResponse<CreateStreamedResponse> Stream of message chunks
     */
    protected function streamChat(string $prompt): StreamResponse
    {
        $params = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'stream' => true,
        ];

        // System message is a separate parameter in Anthropic API
        if ($this->systemMessage) {
            $params['system'] = $this->systemMessage;
        }

        return $this->client->messages()->createStreamed($params);
    }

    /**
     * Extract content from a streamed response chunk.
     *
     * Anthropic streaming events have different types:
     * - message_start: Initial metadata
     * - content_block_start: Begin of a content block
     * - content_block_delta: Incremental content (this is what we want)
     * - content_block_stop: End of content block
     * - message_delta: Message-level changes
     * - message_stop: End of message
     *
     * @param  CreateStreamedResponse  $chunk  The streamed chunk
     * @return string The content delta
     */
    protected function extractStreamedContent(CreateStreamedResponse $chunk): string
    {
        // Check if this is a content_block_delta event with text
        if ($chunk->type === 'content_block_delta' && isset($chunk->delta)) {
            if ($chunk->delta->type === 'text_delta') {
                return $chunk->delta->text ?? '';
            }
        }

        return '';
    }
}
