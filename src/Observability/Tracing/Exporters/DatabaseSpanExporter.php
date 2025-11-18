<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\Exporters;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributes;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;
use OpenTelemetry\SDK\Common\Future\FutureInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;

/**
 * Database Span Exporter
 *
 * Exports OpenTelemetry spans to Laravel database storage.
 * Implements batch processing for performance and provides GenAI-specific
 * attribute extraction following OpenTelemetry semantic conventions.
 *
 * Features:
 * - Batch insertion for performance
 * - Upsert traces (update if exists)
 * - Extract GenAI attributes to dedicated columns
 * - PII redaction based on configuration
 * - Cost estimation
 * - Transaction support
 */
final class DatabaseSpanExporter implements SpanExporterInterface
{
    private array $tracesBuffer = [];
    private array $spansBuffer = [];
    private ?string $connection;

    public function __construct(?string $connection = null)
    {
        $this->connection = $connection ?? config('mindwave-tracing.database.connection');
    }

    /**
     * Export a batch of spans to the database
     *
     * @param  iterable<SpanDataInterface>  $batch
     * @return FutureInterface<bool>
     */
    public function export(iterable $batch, ?CancellationInterface $cancellation = null): FutureInterface
    {
        $exported = 0;

        foreach ($batch as $span) {
            try {
                $this->bufferSpan($span);
                $exported++;
            } catch (\Throwable $e) {
                Log::error('Failed to buffer span', [
                    'span_id' => $span->getContext()->getSpanId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Batch insert all buffered spans
        try {
            $this->flush();

            return new CompletedFuture(true);
        } catch (\Throwable $e) {
            Log::error('Failed to flush span buffer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new CompletedFuture(false);
        }
    }

    /**
     * Force flush any buffered spans
     */
    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        try {
            $this->flush();

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to force flush spans', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Shutdown the exporter
     */
    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        return $this->forceFlush($cancellation);
    }

    /**
     * Buffer a span for batch processing
     */
    private function bufferSpan(SpanDataInterface $span): void
    {
        $traceId = $span->getContext()->getTraceId();

        // Buffer trace (will upsert)
        if (! isset($this->tracesBuffer[$traceId])) {
            $this->tracesBuffer[$traceId] = $this->createTraceRecord($span);
        } else {
            // Update trace record with new span data
            $this->updateTraceRecord($traceId, $span);
        }

        // Buffer span
        $this->spansBuffer[] = $this->createSpanRecord($span);
    }

    /**
     * Create a trace record from a span
     *
     * @return array<string, mixed>
     */
    private function createTraceRecord(SpanDataInterface $span): array
    {
        $traceId = $span->getContext()->getTraceId();
        $attributes = iterator_to_array($span->getAttributes());

        return [
            'id' => Str::uuid()->toString(),
            'trace_id' => $traceId,
            'service_name' => $this->getServiceName($span),
            'start_time' => $span->getStartEpochNanos(),
            'end_time' => $span->getEndEpochNanos(),
            'duration' => $span->getEndEpochNanos() - $span->getStartEpochNanos(),
            'status' => $this->getStatusCode($span),
            'root_span_id' => $span->getParentSpanContext()->isValid()
                ? null
                : $span->getContext()->getSpanId(),
            'total_spans' => 1,
            'total_input_tokens' => $attributes[GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS] ?? 0,
            'total_output_tokens' => $attributes[GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS] ?? 0,
            'estimated_cost' => $this->estimateCost($span),
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Update trace record with data from another span in the same trace
     */
    private function updateTraceRecord(string $traceId, SpanDataInterface $span): void
    {
        if (! isset($this->tracesBuffer[$traceId])) {
            return;
        }

        $attributes = iterator_to_array($span->getAttributes());

        // Update end time and duration to the latest span
        $this->tracesBuffer[$traceId]['end_time'] = max(
            $this->tracesBuffer[$traceId]['end_time'],
            $span->getEndEpochNanos()
        );

        $this->tracesBuffer[$traceId]['duration'] =
            $this->tracesBuffer[$traceId]['end_time'] -
            min($this->tracesBuffer[$traceId]['start_time'], $span->getStartEpochNanos());

        // Update start time if this span started earlier
        $this->tracesBuffer[$traceId]['start_time'] = min(
            $this->tracesBuffer[$traceId]['start_time'],
            $span->getStartEpochNanos()
        );

        // Increment span count
        $this->tracesBuffer[$traceId]['total_spans']++;

        // Accumulate token counts
        $this->tracesBuffer[$traceId]['total_input_tokens'] +=
            $attributes[GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS] ?? 0;
        $this->tracesBuffer[$traceId]['total_output_tokens'] +=
            $attributes[GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS] ?? 0;

        // Accumulate cost
        $this->tracesBuffer[$traceId]['estimated_cost'] += $this->estimateCost($span);

        // Update status if error
        $statusCode = $this->getStatusCode($span);
        if ($statusCode === 'error') {
            $this->tracesBuffer[$traceId]['status'] = 'error';
        }

        $this->tracesBuffer[$traceId]['updated_at'] = now();
    }

    /**
     * Create a span record from OpenTelemetry span data
     *
     * @return array<string, mixed>
     */
    private function createSpanRecord(SpanDataInterface $span): array
    {
        $attributes = iterator_to_array($span->getAttributes());
        $events = [];
        $links = [];

        // Convert events to array
        foreach ($span->getEvents() as $event) {
            $events[] = [
                'name' => $event->getName(),
                'timestamp' => $event->getEpochNanos(),
                'attributes' => iterator_to_array($event->getAttributes()),
            ];
        }

        // Convert links to array
        foreach ($span->getLinks() as $link) {
            $links[] = [
                'trace_id' => $link->getSpanContext()->getTraceId(),
                'span_id' => $link->getSpanContext()->getSpanId(),
                'attributes' => iterator_to_array($link->getAttributes()),
            ];
        }

        return [
            'id' => Str::uuid()->toString(),
            'trace_id' => $span->getContext()->getTraceId(),
            'span_id' => $span->getContext()->getSpanId(),
            'parent_span_id' => $span->getParentSpanContext()->isValid()
                ? $span->getParentSpanContext()->getSpanId()
                : null,
            'name' => $span->getName(),
            'kind' => $this->getSpanKind($span->getKind()),
            'start_time' => $span->getStartEpochNanos(),
            'end_time' => $span->getEndEpochNanos(),
            'duration' => $span->getEndEpochNanos() - $span->getStartEpochNanos(),

            // Extract GenAI attributes to dedicated columns
            'operation_name' => $attributes[GenAiAttributes::GEN_AI_OPERATION_NAME] ?? null,
            'provider_name' => $attributes[GenAiAttributes::GEN_AI_PROVIDER_NAME] ?? null,
            'request_model' => $attributes[GenAiAttributes::GEN_AI_REQUEST_MODEL] ?? null,
            'response_model' => $attributes[GenAiAttributes::GEN_AI_RESPONSE_MODEL] ?? null,
            'input_tokens' => $attributes[GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS] ?? null,
            'output_tokens' => $attributes[GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS] ?? null,
            'cache_read_tokens' => $attributes[GenAiAttributes::GEN_AI_USAGE_CACHE_READ_TOKENS] ?? null,
            'cache_creation_tokens' => $attributes[GenAiAttributes::GEN_AI_USAGE_CACHE_CREATION_TOKENS] ?? null,
            'temperature' => isset($attributes[GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE])
                ? (float) $attributes[GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE]
                : null,
            'max_tokens' => $attributes[GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS] ?? null,
            'top_p' => isset($attributes[GenAiAttributes::GEN_AI_REQUEST_TOP_P])
                ? (float) $attributes[GenAiAttributes::GEN_AI_REQUEST_TOP_P]
                : null,
            'finish_reasons' => isset($attributes[GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS])
                ? json_encode($attributes[GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS])
                : null,

            'status_code' => $this->getStatusCode($span),
            'status_description' => $span->getStatus()->getDescription(),

            // Store all attributes (redacted if configured)
            'attributes' => json_encode($this->redactSensitiveAttributes($attributes)),

            'events' => json_encode($events),
            'links' => json_encode($links),

            'created_at' => now(),
        ];
    }

    /**
     * Flush buffered spans to database
     *
     * @throws \Throwable
     */
    private function flush(): void
    {
        if (empty($this->tracesBuffer) && empty($this->spansBuffer)) {
            return;
        }

        DB::connection($this->connection)->transaction(function (): void {
            // Upsert traces
            if (! empty($this->tracesBuffer)) {
                DB::connection($this->connection)
                    ->table('mindwave_traces')
                    ->upsert(
                        array_values($this->tracesBuffer),
                        ['trace_id'],
                        [
                            'end_time',
                            'duration',
                            'status',
                            'total_spans',
                            'total_input_tokens',
                            'total_output_tokens',
                            'estimated_cost',
                            'updated_at',
                        ]
                    );
            }

            // Insert spans in chunks for performance
            if (! empty($this->spansBuffer)) {
                foreach (array_chunk($this->spansBuffer, 500) as $chunk) {
                    DB::connection($this->connection)
                        ->table('mindwave_spans')
                        ->insert($chunk);
                }
            }
        });

        // Clear buffers
        $this->tracesBuffer = [];
        $this->spansBuffer = [];
    }

    /**
     * Redact sensitive attributes based on configuration
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function redactSensitiveAttributes(array $attributes): array
    {
        // If message capture is enabled, don't redact
        if (config('mindwave-tracing.capture_messages', false)) {
            return $attributes;
        }

        $redactKeys = config('mindwave-tracing.pii_redact', [
            GenAiAttributes::GEN_AI_INPUT_MESSAGES,
            GenAiAttributes::GEN_AI_OUTPUT_MESSAGES,
            GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS,
            GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS,
            GenAiAttributes::GEN_AI_TOOL_CALL_RESULT,
        ]);

        foreach ($redactKeys as $key) {
            if (isset($attributes[$key])) {
                $attributes[$key] = '[REDACTED]';
            }
        }

        return $attributes;
    }

    /**
     * Estimate the cost of a span based on token usage and pricing
     *
     * @return float Cost in USD
     */
    private function estimateCost(SpanDataInterface $span): float
    {
        if (! config('mindwave-tracing.cost_estimation.enabled', true)) {
            return 0.0;
        }

        $attributes = iterator_to_array($span->getAttributes());

        $provider = $attributes[GenAiAttributes::GEN_AI_PROVIDER_NAME] ?? null;
        $model = $attributes[GenAiAttributes::GEN_AI_REQUEST_MODEL] ?? null;
        $inputTokens = $attributes[GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS] ?? 0;
        $outputTokens = $attributes[GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS] ?? 0;

        if (! $provider || ! $model || ($inputTokens === 0 && $outputTokens === 0)) {
            return 0.0;
        }

        // Get pricing from config
        $pricing = config("mindwave-tracing.cost_estimation.pricing.{$provider}.{$model}");

        if (! $pricing) {
            // Try to find pricing by model prefix (e.g., "gpt-4-0613" -> "gpt-4")
            $modelPrefix = $this->getModelPrefix($model);
            $pricing = config("mindwave-tracing.cost_estimation.pricing.{$provider}.{$modelPrefix}");
        }

        if (! $pricing || ! isset($pricing['input']) || ! isset($pricing['output'])) {
            return 0.0;
        }

        // Calculate cost: (tokens / 1000) * price_per_1000_tokens
        $inputCost = ($inputTokens / 1000) * $pricing['input'];
        $outputCost = ($outputTokens / 1000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get model prefix for pricing lookup
     */
    private function getModelPrefix(string $model): string
    {
        // Extract base model name from versioned models
        // "gpt-4-0613" -> "gpt-4"
        // "claude-3-opus-20240229" -> "claude-3-opus"
        $parts = explode('-', $model);

        // Try different prefix lengths
        for ($i = count($parts) - 1; $i >= 2; $i--) {
            if (is_numeric($parts[$i]) || strlen($parts[$i]) > 10) {
                return implode('-', array_slice($parts, 0, $i));
            }
        }

        return $model;
    }

    /**
     * Get service name from span or config
     */
    private function getServiceName(SpanDataInterface $span): string
    {
        // Try to get from resource attributes
        $resource = $span->getResource();
        $attributes = $resource->getAttributes();

        if ($attributes->has('service.name')) {
            return (string) $attributes->get('service.name');
        }

        return config('mindwave-tracing.service_name', config('app.name', 'laravel-app'));
    }

    /**
     * Get status code as string
     */
    private function getStatusCode(SpanDataInterface $span): string
    {
        $status = $span->getStatus();
        $code = $status->getCode();

        return match ($code) {
            0 => 'unset',
            1 => 'ok',
            2 => 'error',
            default => 'unset',
        };
    }

    /**
     * Convert OpenTelemetry SpanKind to string
     */
    private function getSpanKind(int $kind): string
    {
        return match ($kind) {
            SpanKind::KIND_INTERNAL => 'internal',
            SpanKind::KIND_SERVER => 'server',
            SpanKind::KIND_CLIENT => 'client',
            SpanKind::KIND_PRODUCER => 'producer',
            SpanKind::KIND_CONSUMER => 'consumer',
            default => 'internal',
        };
    }
}
