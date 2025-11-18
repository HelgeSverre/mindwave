<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\GenAI;

use Generator;
use Mindwave\Mindwave\Observability\Events\LlmTokenStreamed;
use Mindwave\Mindwave\Observability\Tracing\Span;
use Mindwave\Mindwave\Observability\Tracing\TracerManager;
use Throwable;

/**
 * GenAI Instrumentor - Automatic LLM call tracing
 *
 * This class provides automatic instrumentation for LLM operations following
 * OpenTelemetry GenAI semantic conventions. It wraps LLM driver calls to
 * automatically create spans with proper attributes, token usage, and error handling.
 *
 * Features:
 * - Automatic span creation with GenAI semantic conventions
 * - Token usage tracking and cost estimation
 * - Optional message content capture (opt-in)
 * - Streaming response support
 * - Exception recording with proper status codes
 * - Context propagation for parent-child relationships
 *
 * Usage:
 * ```php
 * $instrumentor = new GenAiInstrumentor($tracerManager);
 *
 * $response = $instrumentor->instrumentChatCompletion(
 *     provider: 'openai',
 *     model: 'gpt-4',
 *     messages: $messages,
 *     options: ['temperature' => 0.7],
 *     execute: fn() => $llm->chat($messages)
 * );
 * ```
 *
 * @see https://opentelemetry.io/docs/specs/semconv/gen-ai/
 */
class GenAiInstrumentor
{
    private TracerManager $tracerManager;
    private bool $captureMessages;
    private bool $enabled;

    /**
     * @param  TracerManager  $tracerManager  Tracer manager for span creation
     * @param  bool  $captureMessages  Whether to capture message content (opt-in for sensitive data)
     * @param  bool  $enabled  Whether instrumentation is enabled
     */
    public function __construct(
        TracerManager $tracerManager,
        bool $captureMessages = false,
        bool $enabled = true
    ) {
        $this->tracerManager = $tracerManager;
        $this->captureMessages = $captureMessages;
        $this->enabled = $enabled;
    }

    /**
     * Instrument a chat completion operation
     *
     * Creates a span for chat-based LLM interactions, capturing request parameters,
     * response metadata, and token usage.
     *
     * @param  string  $provider  Provider name (e.g., "openai", "anthropic")
     * @param  string  $model  Model name (e.g., "gpt-4", "claude-3-opus")
     * @param  array<array<string, mixed>>  $messages  Chat messages
     * @param  array<string, mixed>  $options  Request options (temperature, max_tokens, etc.)
     * @param  callable(): mixed  $execute  Callback that executes the actual LLM call
     * @param  string|null  $serverAddress  Optional server address override
     * @return mixed The response from the execute callback
     *
     * @throws Throwable Re-throws any exception from the execute callback
     */
    public function instrumentChatCompletion(
        string $provider,
        string $model,
        array $messages,
        array $options,
        callable $execute,
        ?string $serverAddress = null
    ): mixed {
        if (! $this->enabled) {
            return $execute();
        }

        $span = $this->createChatSpan($provider, $model, $messages, $options, $serverAddress);
        $scope = $span->activate();

        try {
            $response = $execute();

            // Capture response attributes if available
            $this->captureResponseAttributes($span, $response);

            $span->markAsOk();

            return $response;
        } catch (Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Instrument a text completion operation
     *
     * Creates a span for traditional text completion (non-chat) operations.
     *
     * @param  string  $provider  Provider name
     * @param  string  $model  Model name
     * @param  string  $prompt  The input prompt
     * @param  array<string, mixed>  $options  Request options
     * @param  callable(): mixed  $execute  Callback that executes the actual LLM call
     * @param  string|null  $serverAddress  Optional server address override
     * @return mixed The response from the execute callback
     *
     * @throws Throwable Re-throws any exception from the execute callback
     */
    public function instrumentTextCompletion(
        string $provider,
        string $model,
        string $prompt,
        array $options,
        callable $execute,
        ?string $serverAddress = null
    ): mixed {
        if (! $this->enabled) {
            return $execute();
        }

        $span = $this->createTextCompletionSpan($provider, $model, $prompt, $options, $serverAddress);
        $scope = $span->activate();

        try {
            $response = $execute();

            // Capture response attributes if available
            $this->captureResponseAttributes($span, $response);

            $span->markAsOk();

            return $response;
        } catch (Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Instrument a streamed chat completion operation
     *
     * Creates a span for streaming chat-based LLM interactions. The span remains
     * active throughout the stream lifecycle, tracking cumulative tokens and firing
     * events for each delta. The span is ended only after the stream completes.
     *
     * @param  string  $provider  Provider name (e.g., "openai", "anthropic")
     * @param  string  $model  Model name (e.g., "gpt-4", "claude-3-opus")
     * @param  string  $prompt  The input prompt
     * @param  array<string, mixed>  $options  Request options (temperature, max_tokens, etc.)
     * @param  callable(): Generator<string>  $execute  Callback that executes the streaming LLM call
     * @param  string|null  $serverAddress  Optional server address override
     * @return Generator<string> Yields text deltas from the LLM stream
     *
     * @throws Throwable Re-throws any exception from the execute callback
     */
    public function instrumentStreamedChatCompletion(
        string $provider,
        string $model,
        string $prompt,
        array $options,
        callable $execute,
        ?string $serverAddress = null
    ): Generator {
        if (! $this->enabled) {
            yield from $execute();

            return;
        }

        $span = $this->createTextCompletionSpan($provider, $model, $prompt, $options, $serverAddress);
        $scope = $span->activate();

        $cumulativeTokens = 0;
        $fullResponse = '';
        $finishReason = null;

        try {
            foreach ($execute() as $delta) {
                $cumulativeTokens++;
                $fullResponse .= $delta;

                // Fire event for each streamed token
                event(new LlmTokenStreamed(
                    delta: $delta,
                    cumulativeTokens: $cumulativeTokens,
                    spanId: $span->getSpanId(),
                    traceId: $span->getTraceId(),
                    timestamp: hrtime(true),
                    metadata: [
                        'provider' => $provider,
                        'model' => $model,
                    ]
                ));

                yield $delta;
            }

            // After stream completes, set final attributes
            $span->setGenAiUsage(
                inputTokens: null, // Input tokens not available in streaming
                outputTokens: $cumulativeTokens
            );

            // Optionally capture the full response
            if ($this->captureMessages && $fullResponse !== '') {
                $span->setGenAiOutputMessages([
                    [
                        'role' => 'assistant',
                        'content' => $fullResponse,
                    ],
                ]);
            }

            $span->markAsOk();
        } catch (Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Instrument an embeddings operation
     *
     * Creates a span for vector embedding generation.
     *
     * @param  string  $provider  Provider name
     * @param  string  $model  Model name
     * @param  string|array<string>  $input  Input text(s) for embedding
     * @param  array<string, mixed>  $options  Request options
     * @param  callable(): mixed  $execute  Callback that executes the actual embeddings call
     * @param  string|null  $serverAddress  Optional server address override
     * @return mixed The response from the execute callback
     *
     * @throws Throwable Re-throws any exception from the execute callback
     */
    public function instrumentEmbeddings(
        string $provider,
        string $model,
        string|array $input,
        array $options,
        callable $execute,
        ?string $serverAddress = null
    ): mixed {
        if (! $this->enabled) {
            return $execute();
        }

        $span = $this->createEmbeddingsSpan($provider, $model, $input, $options, $serverAddress);
        $scope = $span->activate();

        try {
            $response = $execute();

            // Capture response attributes if available
            $this->captureEmbeddingsResponse($span, $response);

            $span->markAsOk();

            return $response;
        } catch (Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Instrument a tool/function execution operation
     *
     * Creates a span for tool/function calls made by the LLM.
     *
     * @param  string  $toolName  Name of the tool/function being executed
     * @param  array<string, mixed>  $arguments  Tool arguments
     * @param  callable(): mixed  $execute  Callback that executes the tool
     * @return mixed The result from the execute callback
     *
     * @throws Throwable Re-throws any exception from the execute callback
     */
    public function instrumentToolExecution(
        string $toolName,
        array $arguments,
        callable $execute
    ): mixed {
        if (! $this->enabled) {
            return $execute();
        }

        $span = $this->createToolExecutionSpan($toolName, $arguments);
        $scope = $span->activate();

        try {
            $result = $execute();

            // Optionally capture result (sensitive data)
            if ($this->captureMessages) {
                $span->setAttribute(GenAiAttributes::GEN_AI_TOOL_CALL_RESULT, $result);
            }

            $span->markAsOk();

            return $result;
        } catch (Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Create a span for chat completion
     *
     * @param  array<array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options
     */
    private function createChatSpan(
        string $provider,
        string $model,
        array $messages,
        array $options,
        ?string $serverAddress
    ): Span {
        $span = $this->tracerManager->startSpan(
            sprintf('%s %s', GenAiOperations::CHAT->value, $model),
            [
                GenAiAttributes::GEN_AI_OPERATION_NAME => GenAiOperations::CHAT->value,
                GenAiAttributes::GEN_AI_PROVIDER_NAME => $provider,
                GenAiAttributes::GEN_AI_REQUEST_MODEL => $model,
            ]
        );

        // Set request parameters
        $span->setGenAiRequestParams($options);

        // Set server attributes
        if ($serverAddress) {
            $span->setServerAttributes($serverAddress);
        } elseif ($providerEnum = GenAiProviders::fromString($provider)) {
            $span->setServerAttributes(
                $providerEnum->getDefaultServerAddress(),
                $providerEnum->getDefaultServerPort()
            );
        }

        // Optionally capture messages (sensitive data)
        if ($this->captureMessages) {
            $span->setGenAiInputMessages($messages);
        }

        return $span;
    }

    /**
     * Create a span for text completion
     *
     * @param  array<string, mixed>  $options
     */
    private function createTextCompletionSpan(
        string $provider,
        string $model,
        string $prompt,
        array $options,
        ?string $serverAddress
    ): Span {
        $span = $this->tracerManager->startSpan(
            sprintf('%s %s', GenAiOperations::TEXT_COMPLETION->value, $model),
            [
                GenAiAttributes::GEN_AI_OPERATION_NAME => GenAiOperations::TEXT_COMPLETION->value,
                GenAiAttributes::GEN_AI_PROVIDER_NAME => $provider,
                GenAiAttributes::GEN_AI_REQUEST_MODEL => $model,
            ]
        );

        // Set request parameters
        $span->setGenAiRequestParams($options);

        // Set server attributes
        if ($serverAddress) {
            $span->setServerAttributes($serverAddress);
        } elseif ($providerEnum = GenAiProviders::fromString($provider)) {
            $span->setServerAttributes(
                $providerEnum->getDefaultServerAddress(),
                $providerEnum->getDefaultServerPort()
            );
        }

        // Optionally capture prompt (sensitive data)
        if ($this->captureMessages) {
            $span->setAttribute(GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS, $prompt);
        }

        return $span;
    }

    /**
     * Create a span for embeddings generation
     *
     * @param  string|array<string>  $input
     * @param  array<string, mixed>  $options
     */
    private function createEmbeddingsSpan(
        string $provider,
        string $model,
        string|array $input,
        array $options,
        ?string $serverAddress
    ): Span {
        $span = $this->tracerManager->startSpan(
            sprintf('%s %s', GenAiOperations::EMBEDDINGS->value, $model),
            [
                GenAiAttributes::GEN_AI_OPERATION_NAME => GenAiOperations::EMBEDDINGS->value,
                GenAiAttributes::GEN_AI_PROVIDER_NAME => $provider,
                GenAiAttributes::GEN_AI_REQUEST_MODEL => $model,
            ]
        );

        // Set server attributes
        if ($serverAddress) {
            $span->setServerAttributes($serverAddress);
        } elseif ($providerEnum = GenAiProviders::fromString($provider)) {
            $span->setServerAttributes(
                $providerEnum->getDefaultServerAddress(),
                $providerEnum->getDefaultServerPort()
            );
        }

        // Optionally capture input (sensitive data)
        if ($this->captureMessages) {
            $span->setAttribute(GenAiAttributes::GEN_AI_EMBEDDINGS_INPUT, $input);
        }

        return $span;
    }

    /**
     * Create a span for tool execution
     *
     * @param  array<string, mixed>  $arguments
     */
    private function createToolExecutionSpan(string $toolName, array $arguments): Span
    {
        $span = $this->tracerManager->startSpan(
            sprintf('%s %s', GenAiOperations::EXECUTE_TOOL->value, $toolName),
            [
                GenAiAttributes::GEN_AI_OPERATION_NAME => GenAiOperations::EXECUTE_TOOL->value,
                GenAiAttributes::GEN_AI_TOOL_CALL_NAME => $toolName,
            ]
        );

        // Optionally capture arguments (sensitive data)
        if ($this->captureMessages) {
            $span->setAttribute(GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS, $arguments);
        }

        return $span;
    }

    /**
     * Capture response attributes from LLM response
     *
     * This method handles different response types from various LLM providers.
     * It's designed to be flexible and work with OpenAI, Anthropic, Mistral, etc.
     */
    private function captureResponseAttributes(Span $span, mixed $response): void
    {
        if (! is_object($response)) {
            return;
        }

        // OpenAI ChatResponse structure
        if (isset($response->id)) {
            $span->setAttribute(GenAiAttributes::GEN_AI_RESPONSE_ID, $response->id);
        }

        if (isset($response->model)) {
            $span->setAttribute(GenAiAttributes::GEN_AI_RESPONSE_MODEL, $response->model);
        }

        // Capture finish reasons
        if (isset($response->choices) && is_array($response->choices) && count($response->choices) > 0) {
            $finishReasons = array_map(
                fn ($choice) => $choice->finishReason ?? $choice->finish_reason ?? null,
                $response->choices
            );
            $finishReasons = array_filter($finishReasons, fn ($reason) => $reason !== null);

            if (! empty($finishReasons)) {
                $span->setAttribute(GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS, $finishReasons);
            }
        }

        // Capture token usage
        if (isset($response->usage)) {
            $this->captureTokenUsage($span, $response->usage);
        }

        // Optionally capture output messages
        if ($this->captureMessages && isset($response->choices[0]->message)) {
            $message = $response->choices[0]->message;
            $span->setGenAiOutputMessages([
                [
                    'role' => $message->role ?? 'assistant',
                    'content' => $message->content ?? $message->text ?? '',
                ],
            ]);
        } elseif ($this->captureMessages && isset($response->choices[0]->text)) {
            // Handle completion responses
            $span->setGenAiOutputMessages([
                [
                    'role' => 'assistant',
                    'content' => $response->choices[0]->text,
                ],
            ]);
        }
    }

    /**
     * Capture token usage from response
     */
    private function captureTokenUsage(Span $span, mixed $usage): void
    {
        if (! is_object($usage)) {
            return;
        }

        $inputTokens = $usage->promptTokens ?? $usage->prompt_tokens ?? null;
        $outputTokens = $usage->completionTokens ?? $usage->completion_tokens ?? null;
        $cacheReadTokens = $usage->cacheReadTokens ?? $usage->cache_read_tokens ?? null;
        $cacheCreationTokens = $usage->cacheCreationTokens ?? $usage->cache_creation_tokens ?? null;

        $span->setGenAiUsage(
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cacheReadTokens: $cacheReadTokens,
            cacheCreationTokens: $cacheCreationTokens
        );
    }

    /**
     * Capture embeddings response attributes
     */
    private function captureEmbeddingsResponse(Span $span, mixed $response): void
    {
        if (! is_object($response)) {
            return;
        }

        if (isset($response->id)) {
            $span->setAttribute(GenAiAttributes::GEN_AI_RESPONSE_ID, $response->id);
        }

        if (isset($response->model)) {
            $span->setAttribute(GenAiAttributes::GEN_AI_RESPONSE_MODEL, $response->model);
        }

        // Capture dimension from first embedding
        if (isset($response->data) && is_array($response->data) && count($response->data) > 0) {
            $firstEmbedding = $response->data[0];
            if (isset($firstEmbedding->embedding) && is_array($firstEmbedding->embedding)) {
                $dimension = count($firstEmbedding->embedding);
                $span->setAttribute(GenAiAttributes::GEN_AI_EMBEDDINGS_DIMENSION, $dimension);
            }
        }

        // Capture token usage if available
        if (isset($response->usage)) {
            $this->captureTokenUsage($span, $response->usage);
        }
    }

    /**
     * Enable or disable instrumentation at runtime
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Check if instrumentation is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable or disable message capture at runtime
     */
    public function setCaptureMessages(bool $captureMessages): self
    {
        $this->captureMessages = $captureMessages;

        return $this;
    }

    /**
     * Check if message capture is enabled
     */
    public function isCaptureMessagesEnabled(): bool
    {
        return $this->captureMessages;
    }

    /**
     * Get the tracer manager
     */
    public function getTracerManager(): TracerManager
    {
        return $this->tracerManager;
    }
}
