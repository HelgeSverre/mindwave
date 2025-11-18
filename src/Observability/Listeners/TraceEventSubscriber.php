<?php

namespace Mindwave\Mindwave\Observability\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Mindwave\Mindwave\Observability\Events\LlmErrorOccurred;
use Mindwave\Mindwave\Observability\Events\LlmRequestStarted;
use Mindwave\Mindwave\Observability\Events\LlmResponseCompleted;
use Mindwave\Mindwave\Observability\Events\LlmTokenStreamed;

/**
 * Event subscriber for LLM tracing events.
 *
 * This subscriber listens to all LLM lifecycle events and can be used for:
 * - Logging important events
 * - Collecting metrics
 * - Custom monitoring and alerting
 * - Integration with external observability tools
 */
class TraceEventSubscriber
{
    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string|array>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            LlmRequestStarted::class => 'handleRequestStarted',
            LlmTokenStreamed::class => 'handleTokenStreamed',
            LlmResponseCompleted::class => 'handleResponseCompleted',
            LlmErrorOccurred::class => 'handleErrorOccurred',
        ];
    }

    /**
     * Handle the LlmRequestStarted event.
     */
    public function handleRequestStarted(LlmRequestStarted $event): void
    {
        if (config('mindwave-llm.tracing.log_events', false)) {
            Log::debug('LLM request started', [
                'trace_id' => $event->traceId,
                'span_id' => $event->spanId,
                'provider' => $event->provider,
                'model' => $event->model,
                'operation' => $event->operation,
                'parameters' => $event->parameters,
            ]);
        }
    }

    /**
     * Handle the LlmTokenStreamed event.
     */
    public function handleTokenStreamed(LlmTokenStreamed $event): void
    {
        // Only log final tokens to avoid excessive logging
        if ($event->isFinal() && config('mindwave-llm.tracing.log_events', false)) {
            Log::debug('LLM streaming completed', [
                'trace_id' => $event->traceId,
                'span_id' => $event->spanId,
                'cumulative_tokens' => $event->cumulativeTokens,
                'finish_reason' => $event->getFinishReason(),
            ]);
        }
    }

    /**
     * Handle the LlmResponseCompleted event.
     */
    public function handleResponseCompleted(LlmResponseCompleted $event): void
    {
        if (config('mindwave-llm.tracing.log_events', false)) {
            $logData = [
                'trace_id' => $event->traceId,
                'span_id' => $event->spanId,
                'provider' => $event->provider,
                'model' => $event->model,
                'operation' => $event->operation,
                'duration_ms' => $event->getDurationInMilliseconds(),
                'input_tokens' => $event->getInputTokens(),
                'output_tokens' => $event->getOutputTokens(),
                'total_tokens' => $event->getTotalTokens(),
                'tokens_per_second' => round($event->getTokensPerSecond(), 2),
            ];

            if ($event->hasCostEstimate()) {
                $logData['cost_estimate'] = $event->getFormattedCost();
            }

            if ($event->usedCache()) {
                $logData['cache_read_tokens'] = $event->getCacheReadTokens();
                $logData['cache_creation_tokens'] = $event->getCacheCreationTokens();
            }

            Log::info('LLM response completed', $logData);
        }

        // Log slow requests
        $slowThresholdMs = config('mindwave-llm.tracing.slow_threshold_ms', 5000);
        if ($event->getDurationInMilliseconds() > $slowThresholdMs) {
            Log::warning('Slow LLM request detected', [
                'trace_id' => $event->traceId,
                'span_id' => $event->spanId,
                'provider' => $event->provider,
                'model' => $event->model,
                'duration_ms' => $event->getDurationInMilliseconds(),
                'threshold_ms' => $slowThresholdMs,
            ]);
        }

        // Log high-cost requests
        if ($event->hasCostEstimate()) {
            $costThreshold = config('mindwave-llm.tracing.cost_threshold', 0.1);
            if ($event->costEstimate > $costThreshold) {
                Log::warning('High-cost LLM request detected', [
                    'trace_id' => $event->traceId,
                    'span_id' => $event->spanId,
                    'provider' => $event->provider,
                    'model' => $event->model,
                    'cost_estimate' => $event->getFormattedCost(),
                    'threshold' => '$'.number_format($costThreshold, 4),
                    'total_tokens' => $event->getTotalTokens(),
                ]);
            }
        }
    }

    /**
     * Handle the LlmErrorOccurred event.
     */
    public function handleErrorOccurred(LlmErrorOccurred $event): void
    {
        Log::error('LLM request failed', [
            'trace_id' => $event->traceId,
            'span_id' => $event->spanId,
            'provider' => $event->provider,
            'model' => $event->model,
            'operation' => $event->operation,
            'exception' => $event->getExceptionClass(),
            'message' => $event->getMessage(),
            'code' => $event->getCode(),
            'file' => $event->getFile(),
            'line' => $event->getLine(),
            'context' => $event->context,
        ]);
    }
}
