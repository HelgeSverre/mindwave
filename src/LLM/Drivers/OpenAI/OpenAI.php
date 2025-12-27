<?php

namespace Mindwave\Mindwave\LLM\Drivers\OpenAI;

use Generator;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Exceptions\StreamingException;
use Mindwave\Mindwave\LLM\Drivers\BaseDriver;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionBuilder;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionCall;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use OpenAI\Responses\Chat\CreateStreamedResponse as StreamedChatResponse;
use OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use OpenAI\Responses\StreamResponse;
use Throwable;

class OpenAI extends BaseDriver implements LLM
{
    public function __construct(
        protected ClientContract $client,
        protected string $model = ModelNames::GPT4_1106_PREVIEW,
        protected ?string $systemMessage = null,
        protected int $maxTokens = 800,
        protected float $temperature = 0.7,
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

    public function functionCall(string $prompt, array|FunctionBuilder $functions, ?string $requiredFunction = 'auto'): FunctionCall|string|null
    {
        /** @var ChatResponse $response */
        $response = $this->client->chat()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $prompt,
                ],
            ],
            'tools' => $functions instanceof FunctionBuilder ? $functions->build() : $functions,
            'tool_choice' => match ($requiredFunction) {
                null, 'auto' => 'auto',
                'none' => 'none',
                default => ['type' => 'function', 'function' => ['name' => $requiredFunction]],
            },
        ]);

        $choice = $response->choices[0];

        if ($choice->message->toolCalls) {
            return new FunctionCall(
                name: $choice->message->toolCalls[0]->function->name,
                arguments: rescue(fn () => json_decode($choice->message->toolCalls[0]->function->arguments, true), report: false),
                rawArguments: $choice->message->toolCalls[0]->function->arguments,
            );
        }

        return $response->choices[0]->message->content;
    }

    public function generateText(string $prompt): ?string
    {
        if (ModelNames::isCompletionModel($this->model)) {
            $response = $this->completion($prompt);

            return $this->extractResponseText($response);
        }

        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        return $response->content;
    }

    protected function extractResponseText(CompletionResponse $response): string
    {
        return $response->choices[0]->text;
    }

    public function chat(array $messages, array $options = []): \Mindwave\Mindwave\LLM\Responses\ChatResponse
    {
        $response = $this->client->chat()->create(array_merge([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'messages' => $messages,
        ], $options));

        return new \Mindwave\Mindwave\LLM\Responses\ChatResponse(
            content: $response->choices[0]->message->content,
            role: $response->choices[0]->message->role,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            finishReason: $response->choices[0]->finishReason,
            model: $response->model,
            raw: (array) $response->toArray(),
        );
    }

    public function completion($prompt): CompletionResponse
    {
        return $this->client->completions()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'prompt' => $prompt,
        ]);
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
            $stream = ModelNames::isCompletionModel($this->model)
                ? $this->streamCompletion($prompt)
                : $this->streamChatRaw($prompt);

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
            throw StreamingException::connectionFailed('openai', $this->model, $e);
        }
    }

    /**
     * Create a streaming chat completion request (raw stream).
     *
     * @param  string  $prompt  The prompt to send
     * @return StreamResponse<StreamedChatResponse> Stream of chat completion chunks
     */
    protected function streamChatRaw(string $prompt): StreamResponse
    {
        return $this->client->chat()->createStreamed([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'messages' => $this->systemMessage
                ? [
                    ['role' => 'system', 'content' => $this->systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ]
                : [
                    ['role' => 'user', 'content' => $prompt],
                ],
            'stream' => true,
        ]);
    }

    /**
     * Create a streaming completion request.
     *
     * @param  string  $prompt  The prompt to send
     * @return StreamResponse Stream of completion chunks
     */
    protected function streamCompletion(string $prompt): StreamResponse
    {
        return $this->client->completions()->createStreamed([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => true,
        ]);
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
            $stream = $this->client->chat()->createStreamed(array_merge([
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'model' => $this->model,
                'messages' => $messages,
                'stream' => true,
            ], $options));

            foreach ($stream as $response) {
                yield $this->mapToStreamChunk($response);
            }
        } catch (Throwable $e) {
            throw StreamingException::connectionFailed('openai', $this->model, $e);
        }
    }

    /**
     * Map OpenAI streamed response to StreamChunk.
     *
     * @param  StreamedChatResponse  $response  The OpenAI streamed response
     * @return StreamChunk The mapped chunk
     */
    protected function mapToStreamChunk(StreamedChatResponse $response): StreamChunk
    {
        $choice = $response->choices[0] ?? null;

        if (! $choice) {
            return new StreamChunk(raw: (array) $response->toArray());
        }

        return new StreamChunk(
            content: $choice->delta->content ?? null,
            role: $choice->delta->role ?? null,
            finishReason: $choice->finishReason ?? null,
            model: $response->model ?? null,
            inputTokens: null, // OpenAI doesn't provide incremental token counts
            outputTokens: null,
            toolCalls: isset($choice->delta->toolCalls) ? (array) $choice->delta->toolCalls : null,
            raw: (array) $response->toArray(),
        );
    }

    /**
     * Extract content from a streamed response chunk.
     *
     * @param  mixed  $chunk  The streamed chunk
     * @return string The content delta
     */
    protected function extractStreamedContent(mixed $chunk): string
    {
        // For chat completions
        if (isset($chunk->choices[0]->delta->content)) {
            return $chunk->choices[0]->delta->content ?? '';
        }

        // For legacy completions
        if (isset($chunk->choices[0]->text)) {
            return $chunk->choices[0]->text ?? '';
        }

        return '';
    }
}
