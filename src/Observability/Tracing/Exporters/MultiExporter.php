<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\Exporters;

use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;
use OpenTelemetry\SDK\Common\Future\FutureInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Multi-backend span exporter
 *
 * This exporter fans out span exports to multiple backend exporters simultaneously,
 * allowing traces to be sent to multiple destinations (e.g., database + OTLP + custom).
 *
 * Features:
 * - Parallel export to all configured exporters
 * - Graceful handling of partial failures
 * - Aggregates results across all exporters
 * - Logs individual exporter failures without failing entire export
 * - Returns success if at least one exporter succeeds
 *
 * Use Cases:
 * - Send traces to both local database and remote OTLP backend
 * - Export to multiple observability platforms simultaneously
 * - Maintain local backup while sending to cloud service
 * - A/B testing different tracing backends
 */
final class MultiExporter implements SpanExporterInterface
{
    /**
     * @var array<int, SpanExporterInterface> Array of exporters to fan out to
     */
    private array $exporters;

    /**
     * @var LoggerInterface Logger for reporting export failures
     */
    private LoggerInterface $logger;

    /**
     * @var bool Whether to fail if all exporters fail (vs. just logging)
     */
    private bool $failOnAllErrors;

    /**
     * @var array<string, int> Statistics tracking export results
     */
    private array $stats = [
        'total_exports' => 0,
        'successful_exports' => 0,
        'failed_exports' => 0,
        'total_spans_exported' => 0,
    ];

    /**
     * Constructor
     *
     * @param array<int, SpanExporterInterface> $exporters Array of exporters to use
     * @param LoggerInterface|null $logger Optional logger for error reporting
     * @param bool $failOnAllErrors If true, export() returns failure only if ALL exporters fail
     */
    public function __construct(
        array $exporters,
        ?LoggerInterface $logger = null,
        bool $failOnAllErrors = false
    ) {
        if (empty($exporters)) {
            throw new \InvalidArgumentException(
                'MultiExporter requires at least one exporter'
            );
        }

        // Validate all items are exporters
        foreach ($exporters as $index => $exporter) {
            if (! $exporter instanceof SpanExporterInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Exporter at index %d must implement %s, got %s',
                        $index,
                        SpanExporterInterface::class,
                        get_debug_type($exporter)
                    )
                );
            }
        }

        $this->exporters = array_values($exporters); // Re-index
        $this->logger = $logger ?? new NullLogger();
        $this->failOnAllErrors = $failOnAllErrors;

        $this->logger->info('MultiExporter initialized', [
            'exporter_count' => count($this->exporters),
            'fail_on_all_errors' => $failOnAllErrors,
        ]);
    }

    /**
     * Export spans to all configured exporters
     *
     * This method fans out the export call to all registered exporters in parallel.
     * Each exporter's result is tracked independently:
     *
     * - If an exporter succeeds, its success is recorded
     * - If an exporter fails, the error is logged but export continues
     * - Overall export is considered successful if ANY exporter succeeds
     * - If ALL exporters fail, the result depends on $failOnAllErrors setting
     *
     * @param iterable $batch Batch of spans to export
     * @param CancellationInterface|null $cancellation Optional cancellation interface
     * @return FutureInterface<bool> Future that resolves to true on success
     */
    public function export(iterable $batch, ?CancellationInterface $cancellation = null): FutureInterface
    {
        // Convert to array to allow multiple iterations
        $spans = is_array($batch) ? $batch : iterator_to_array($batch);
        $spanCount = count($spans);

        if ($spanCount === 0) {
            $this->logger->debug('MultiExporter received empty batch, skipping');
            return new CompletedFuture(true);
        }

        $this->logger->debug('MultiExporter starting batch export', [
            'span_count' => $spanCount,
            'exporter_count' => count($this->exporters),
        ]);

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        // Export to each exporter
        foreach ($this->exporters as $index => $exporter) {
            $exporterName = $this->getExporterName($exporter, $index);

            try {
                // Export returns a Future, so we need to await it
                $future = $exporter->export($spans, $cancellation);
                $success = $future->await();

                $results[$exporterName] = [
                    'success' => $success,
                ];

                if ($success) {
                    $successCount++;
                    $this->logger->debug('Exporter succeeded', [
                        'exporter' => $exporterName,
                        'span_count' => $spanCount,
                    ]);
                } else {
                    $failureCount++;
                    $this->logger->warning('Exporter returned failure', [
                        'exporter' => $exporterName,
                        'span_count' => $spanCount,
                    ]);
                }
            } catch (Throwable $e) {
                $failureCount++;
                $results[$exporterName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                $this->logger->error('Exporter threw exception', [
                    'exporter' => $exporterName,
                    'error' => $e->getMessage(),
                    'exception_class' => get_class($e),
                    'span_count' => $spanCount,
                ]);
            }
        }

        // Update statistics
        $this->stats['total_exports']++;
        $this->stats['successful_exports'] += $successCount;
        $this->stats['failed_exports'] += $failureCount;
        $this->stats['total_spans_exported'] += $spanCount;

        // Determine overall result
        $overallSuccess = $this->determineOverallSuccess($successCount, $failureCount);

        $this->logger->info('MultiExporter batch complete', [
            'span_count' => $spanCount,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'overall_success' => $overallSuccess,
            'results' => $results,
        ]);

        return new CompletedFuture($overallSuccess);
    }

    /**
     * Shutdown all exporters
     *
     * Calls shutdown() on each exporter to allow graceful cleanup.
     * Continues shutting down all exporters even if some fail.
     *
     * @param CancellationInterface|null $cancellation Optional cancellation interface
     * @return bool True if ALL exporters shut down successfully
     */
    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        $this->logger->info('Shutting down MultiExporter', [
            'exporter_count' => count($this->exporters),
        ]);

        $successCount = 0;
        $failureCount = 0;

        foreach ($this->exporters as $index => $exporter) {
            $exporterName = $this->getExporterName($exporter, $index);

            try {
                $success = $exporter->shutdown($cancellation);

                if ($success) {
                    $successCount++;
                    $this->logger->debug('Exporter shutdown succeeded', [
                        'exporter' => $exporterName,
                    ]);
                } else {
                    $failureCount++;
                    $this->logger->warning('Exporter shutdown returned false', [
                        'exporter' => $exporterName,
                    ]);
                }
            } catch (Throwable $e) {
                $failureCount++;
                $this->logger->error('Exporter shutdown threw exception', [
                    'exporter' => $exporterName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('MultiExporter shutdown complete', [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'stats' => $this->stats,
        ]);

        // Return success only if ALL exporters shut down successfully
        return $failureCount === 0;
    }

    /**
     * Force flush all exporters
     *
     * Forces all exporters to immediately export any buffered spans.
     * This is called before shutdown or when immediate export is needed.
     *
     * @param CancellationInterface|null $cancellation Optional cancellation interface
     * @return bool True if ALL exporters flushed successfully
     */
    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        $this->logger->debug('Force flushing MultiExporter', [
            'exporter_count' => count($this->exporters),
        ]);

        $allSuccess = true;

        foreach ($this->exporters as $index => $exporter) {
            $exporterName = $this->getExporterName($exporter, $index);

            try {
                $success = $exporter->forceFlush($cancellation);

                if (! $success) {
                    $allSuccess = false;
                    $this->logger->warning('Exporter force flush failed', [
                        'exporter' => $exporterName,
                    ]);
                } else {
                    $this->logger->debug('Exporter force flush succeeded', [
                        'exporter' => $exporterName,
                    ]);
                }
            } catch (Throwable $e) {
                $allSuccess = false;
                $this->logger->error('Exporter force flush threw exception', [
                    'exporter' => $exporterName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('MultiExporter force flush complete', [
            'all_success' => $allSuccess,
        ]);

        return $allSuccess;
    }

    /**
     * Get statistics about export operations
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get the number of configured exporters
     *
     * @return int
     */
    public function getExporterCount(): int
    {
        return count($this->exporters);
    }

    /**
     * Reset statistics counters
     *
     * @return void
     */
    public function resetStats(): void
    {
        $this->stats = [
            'total_exports' => 0,
            'successful_exports' => 0,
            'failed_exports' => 0,
            'total_spans_exported' => 0,
        ];
    }

    /**
     * Determine overall export success based on individual results
     *
     * @param int $successCount Number of successful exports
     * @param int $failureCount Number of failed exports
     * @return bool Overall success status
     */
    private function determineOverallSuccess(
        int $successCount,
        int $failureCount
    ): bool {
        // If at least one exporter succeeded, consider it a success
        if ($successCount > 0) {
            return true;
        }

        // All exporters failed
        if ($this->failOnAllErrors) {
            return false;
        }

        // If failOnAllErrors is false, log but return success
        // This allows the application to continue even if all exporters fail
        return true;
    }

    /**
     * Get a human-readable name for an exporter
     *
     * @param SpanExporterInterface $exporter
     * @param int $index
     * @return string
     */
    private function getExporterName(SpanExporterInterface $exporter, int $index): string
    {
        $className = get_class($exporter);
        $shortName = substr($className, strrpos($className, '\\') + 1);

        return sprintf('%s#%d', $shortName, $index);
    }
}
