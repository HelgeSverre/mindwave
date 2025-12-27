<?php

namespace Mindwave\Mindwave\LLM\Drivers\Anthropic;

use Anthropic\Contracts\ClientContract;
use Anthropic\Responses\Messages\CreateStreamedResponse;
use Anthropic\Responses\Messages\StreamResponse;
use Generator;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Exceptions\StreamingException;
use Mindwave\Mindwave\LLM\Drivers\BaseDriver;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use Throwable;

class AnthropicDriver extends BaseDriver implements LLM
{
    public function __construct(
        protected ClientContract $client,
        protected string $model = ModelNames::CLAUDE_3_5_SONNET,
        protected ?string $systemMessage = null,
        protected int $maxTokens = 4096,
        protected float $temperature = 1.0,
    ) {}

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
        try {
            $stream = $this->streamChatRaw($prompt);

            foreach ($stream as $chunk) {
                $content = $this->extractStreamedContent($chunk);
                if ($content !== '') {
                    yield $content;
                }
            }
        } catch (Throwable $e) {
            if ($e instanceof StreamingException) {
                throw $e;
            }
            throw StreamingException::connectionFailed('anthropic', $this->model, $e);
        }
    }

    /**
     * Stream a chat completion with structured response chunks.
     *
     * @param  array<array{role: string, content: string}>  $messages  Array of messages
     * @param  array  $options  Additional options
     * @return Generator<StreamChunk> Yields structured chunks with content and metadata
     */
    public function streamChat(array $messages, array $options = []): Generator
    {
        try {
            $params = array_merge([
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'messages' => $messages,
                'stream' => true,
            ], $options);

            // System message is a separate parameter in Anthropic API
            if ($this->systemMessage) {
                $params['system'] = $this->systemMessage;
            }

            $stream = $this->client->messages()->createStreamed($params);

            foreach ($stream as $response) {
                yield $this->mapToStreamChunk($response);
            }
        } catch (Throwable $e) {
            throw StreamingException::connectionFailed('anthropic', $this->model, $e);
        }
    }

    /**
     * Create a streaming chat completion request (raw stream).
     *
     * @param  string  $prompt  The prompt to send
     * @return StreamResponse<CreateStreamedResponse> Stream of message chunks
     */
    protected function streamChatRaw(string $prompt): StreamResponse
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
     * Map Anthropic streamed response to StreamChunk.
     *
     * @param  CreateStreamedResponse  $response  The Anthropic streamed response
     * @return StreamChunk The mapped chunk
     */
    protected function mapToStreamChunk(CreateStreamedResponse $response): StreamChunk
    {
        // Handle different event types
        return match ($response->type) {
            'message_start' => new StreamChunk(
                role: $response->message->role ?? null,
                model: $response->message->model ?? null,
                inputTokens: $response->message->usage->inputTokens ?? null,
                raw: (array) $response->toArray(),
            ),
            'content_block_delta' => new StreamChunk(
                content: $response->delta->type === 'text_delta' ? ($response->delta->text ?? null) : null,
                raw: (array) $response->toArray(),
            ),
            'message_delta' => new StreamChunk(
                finishReason: $response->delta->stop_reason ?? null,
                outputTokens: $response->usage->outputTokens ?? null,
                raw: (array) $response->toArray(),
            ),
            default => new StreamChunk(raw: (array) $response->toArray()),
        };
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
