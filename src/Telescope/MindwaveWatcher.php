<?php

namespace Mindwave\Mindwave\Telescope;

use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\Watcher;
use Mindwave\Mindwave\Observability\Events\LlmErrorOccurred;
use Mindwave\Mindwave\Observability\Events\LlmRequestStarted;
use Mindwave\Mindwave\Observability\Events\LlmResponseCompleted;

/**
 * Telescope Watcher for Mindwave LLM calls.
 *
 * This watcher records LLM API calls to Telescope as client requests,
 * allowing you to monitor AI interactions alongside your other HTTP traffic.
 *
 * Configuration (in config/telescope.php):
 *
 *     'watchers' => [
 *         \Mindwave\Mindwave\Telescope\MindwaveWatcher::class => [
 *             'enabled' => env('MINDWAVE_TELESCOPE_ENABLED', true),
 *             'slow' => 5000,           // ms - tag as "slow" above this
 *             'cost_threshold' => 0.10, // $ - tag as "expensive" above this
 *             'capture_messages' => true, // Set false to hide prompt/response content
 *         ],
 *     ],
 */
class MindwaveWatcher extends Watcher
{
    /**
     * Pending requests indexed by span ID for correlation.
     */
    protected array $pendingRequests = [];

    /**
     * Register the watcher.
     */
    public function register($app): void
    {
        $app['events']->listen(LlmRequestStarted::class, [$this, 'recordRequest']);
        $app['events']->listen(LlmResponseCompleted::class, [$this, 'recordResponse']);
        $app['events']->listen(LlmErrorOccurred::class, [$this, 'recordError']);
    }

    /**
     * Record an LLM request starting.
     */
    public function recordRequest(LlmRequestStarted $event): void
    {
        $this->pendingRequests[$event->spanId] = [
            'messages' => $this->shouldCaptureMessages() ? $event->messages : null,
            'parameters' => $event->parameters,
            'started_at' => $event->timestamp,
        ];
    }

    /**
     * Record an LLM response completion.
     */
    public function recordResponse(LlmResponseCompleted $event): void
    {
        $request = $this->pendingRequests[$event->spanId] ?? [];
        unset($this->pendingRequests[$event->spanId]);

        Telescope::recordClientRequest(IncomingEntry::make([
            'method' => 'POST',
            'uri' => $this->formatUri($event->provider, $event->operation, $event->model),
            'headers' => [],
            'payload' => $this->formatPayload($request),
            'response_status' => 200,
            'response' => $this->formatResponse($event),
            'duration' => $event->getDurationInMilliseconds(),
            'mindwave' => $this->buildMindwaveData($event),
        ])->tags($this->buildTags($event)));
    }

    /**
     * Record an LLM error.
     */
    public function recordError(LlmErrorOccurred $event): void
    {
        $request = $this->pendingRequests[$event->spanId] ?? [];
        unset($this->pendingRequests[$event->spanId]);

        $statusCode = $event->getCode();
        if (! is_int($statusCode) || $statusCode < 100 || $statusCode > 599) {
            $statusCode = 500;
        }

        Telescope::recordClientRequest(IncomingEntry::make([
            'method' => 'POST',
            'uri' => $this->formatUri($event->provider, $event->operation, $event->model),
            'headers' => [],
            'payload' => $this->formatPayload($request),
            'response_status' => $statusCode,
            'response' => [
                'error' => true,
                'exception' => $event->getExceptionClass(),
                'message' => $event->getMessage(),
            ],
            'duration' => null,
            'mindwave' => [
                'provider' => $event->provider,
                'model' => $event->model,
                'operation' => $event->operation,
                'error' => true,
                'exception_class' => $event->getExceptionClass(),
                'exception_message' => $event->getMessage(),
                'trace_id' => $event->traceId,
                'span_id' => $event->spanId,
            ],
        ])->tags($this->buildErrorTags($event)));
    }

    /**
     * Format the URI for the LLM call.
     */
    protected function formatUri(string $provider, string $operation, string $model): string
    {
        return "{$provider}://{$operation}/{$model}";
    }

    /**
     * Format the request payload.
     */
    protected function formatPayload(array $request): array|string
    {
        if (! $this->shouldCaptureMessages()) {
            return '[capture disabled]';
        }

        return [
            'messages' => $request['messages'] ?? null,
            'parameters' => $request['parameters'] ?? [],
        ];
    }

    /**
     * Format the response data.
     */
    protected function formatResponse(LlmResponseCompleted $event): array|string
    {
        if (! $this->shouldCaptureMessages()) {
            return '[capture disabled]';
        }

        return $event->response;
    }

    /**
     * Build the Mindwave-specific data for the entry.
     */
    protected function buildMindwaveData(LlmResponseCompleted $event): array
    {
        return [
            'provider' => $event->provider,
            'model' => $event->model,
            'operation' => $event->operation,
            'input_tokens' => $event->getInputTokens(),
            'output_tokens' => $event->getOutputTokens(),
            'total_tokens' => $event->getTotalTokens(),
            'cache_read_tokens' => $event->getCacheReadTokens(),
            'cache_creation_tokens' => $event->getCacheCreationTokens(),
            'used_cache' => $event->usedCache(),
            'cost' => $event->getFormattedCost(),
            'cost_raw' => $event->costEstimate,
            'finish_reason' => $event->getFinishReason(),
            'response_id' => $event->getResponseId(),
            'tokens_per_second' => round($event->getTokensPerSecond(), 2),
            'trace_id' => $event->traceId,
            'span_id' => $event->spanId,
        ];
    }

    /**
     * Build tags for a successful response.
     */
    protected function buildTags(LlmResponseCompleted $event): array
    {
        $tags = [
            'mindwave',
            "provider:{$event->provider}",
            "model:{$event->model}",
            "operation:{$event->operation}",
        ];

        $slowThreshold = $this->options['slow'] ?? 5000;
        if ($event->getDurationInMilliseconds() > $slowThreshold) {
            $tags[] = 'slow';
        }

        $costThreshold = $this->options['cost_threshold'] ?? 0.10;
        if (($event->costEstimate ?? 0) > $costThreshold) {
            $tags[] = 'expensive';
        }

        if ($event->usedCache()) {
            $tags[] = 'cached';
        }

        return $tags;
    }

    /**
     * Build tags for an error response.
     */
    protected function buildErrorTags(LlmErrorOccurred $event): array
    {
        return [
            'mindwave',
            'mindwave-error',
            "provider:{$event->provider}",
            "model:{$event->model}",
            "operation:{$event->operation}",
        ];
    }

    /**
     * Determine if messages should be captured.
     */
    protected function shouldCaptureMessages(): bool
    {
        return $this->options['capture_messages'] ?? true;
    }
}
