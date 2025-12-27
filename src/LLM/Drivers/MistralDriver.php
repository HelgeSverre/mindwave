<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Generator;
use HelgeSverre\Mistral\Mistral;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Exceptions\StreamingException;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use Throwable;

/**
 * Mistral AI LLM Driver
 *
 * Supports both standard and streaming text generation.
 */
class MistralDriver extends BaseDriver implements LLM
{
    public function __construct(
        protected Mistral $client,
        protected string $model = 'mistral-medium',
        protected ?string $systemMessage = null,
        protected int $maxTokens = 800,
        protected float $temperature = 0.7,
        protected bool $safeMode = false,
        protected ?int $randomSeed = null
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
        $response = $this->client->chat()->create(array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'maxTokens' => $this->maxTokens,
            'safeMode' => $this->safeMode,
            'randomSeed' => $this->randomSeed,
        ], $options));

        return new \Mindwave\Mindwave\LLM\Responses\ChatResponse(
            content: $response->choices[0]->message->content,
            role: $response->choices[0]->message->role,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            finishReason: $response->choices[0]->finishReason,
            model: $response->model,
            raw: (array) $response,
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
            $messages = $this->systemMessage
                ? [
                    ['role' => 'system', 'content' => $this->systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ]
                : [
                    ['role' => 'user', 'content' => $prompt],
                ];

            $stream = $this->client->chat()->createStreamed(
                messages: $messages,
                model: $this->model,
                temperature: $this->temperature,
                maxTokens: $this->maxTokens,
                safeMode: $this->safeMode,
                randomSeed: $this->randomSeed,
            );

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
            throw StreamingException::connectionFailed('mistral', $this->model, $e);
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
            $stream = $this->client->chat()->createStreamed(
                messages: $messages,
                model: $this->model,
                temperature: $this->temperature,
                maxTokens: $this->maxTokens,
                safeMode: $this->safeMode,
                randomSeed: $this->randomSeed,
            );

            foreach ($stream as $response) {
                yield $this->mapToStreamChunk($response);
            }
        } catch (Throwable $e) {
            throw StreamingException::connectionFailed('mistral', $this->model, $e);
        }
    }

    /**
     * Map Mistral streamed response to StreamChunk.
     *
     * @param  \HelgeSverre\Mistral\Dto\Chat\StreamedChatCompletionResponse  $response  The Mistral streamed response
     * @return StreamChunk The mapped chunk
     */
    protected function mapToStreamChunk($response): StreamChunk
    {
        $choice = $response->choices[0] ?? null;

        if (! $choice) {
            return new StreamChunk(raw: (array) $response);
        }

        return new StreamChunk(
            content: $choice->delta->content ?? null,
            role: $choice->delta->role ?? null,
            finishReason: $choice->finishReason ?? null,
            model: $response->model ?? null,
            inputTokens: $response->usage->promptTokens ?? null,
            outputTokens: $response->usage->completionTokens ?? null,
            raw: (array) $response,
        );
    }

    /**
     * Extract content from a streamed response chunk.
     *
     * @param  \HelgeSverre\Mistral\Dto\Chat\StreamedChatCompletionResponse  $chunk  The streamed chunk
     * @return string The content delta
     */
    protected function extractStreamedContent($chunk): string
    {
        // Mistral streaming chunks have choices array with delta containing content
        if (isset($chunk->choices[0]->delta->content)) {
            return $chunk->choices[0]->delta->content ?? '';
        }

        return '';
    }
}
