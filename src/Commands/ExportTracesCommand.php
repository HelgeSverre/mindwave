<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mindwave\Mindwave\Observability\Models\Trace;

class ExportTracesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mindwave:export-traces
                            {--since= : Export traces from this date (e.g., yesterday, 2024-01-01)}
                            {--until= : Export traces until this date}
                            {--format=json : Output format (csv, json, ndjson)}
                            {--output= : Output file path (default: stdout)}
                            {--provider= : Filter by provider name}
                            {--min-cost= : Filter by minimum cost in USD}
                            {--slow= : Filter by minimum duration in milliseconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export traces to CSV/JSON/NDJSON format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');
        $outputPath = $this->option('output');

        // Validate format
        if (! in_array($format, ['csv', 'json', 'ndjson'])) {
            $this->error("Invalid format: {$format}. Must be csv, json, or ndjson.");

            return self::FAILURE;
        }

        // Build query
        $query = $this->buildQuery();

        // Get total count
        $total = $query->count();

        if ($total === 0) {
            $this->warn('No traces found matching the criteria.');

            return self::SUCCESS;
        }

        $this->info("Exporting {$total} traces in {$format} format...");

        // Open output stream
        $output = $outputPath ? fopen($outputPath, 'w') : STDOUT;

        if ($output === false) {
            $this->error("Failed to open output file: {$outputPath}");

            return self::FAILURE;
        }

        try {
            // Export based on format
            match ($format) {
                'csv' => $this->exportCsv($query, $output, $total),
                'json' => $this->exportJson($query, $output, $total),
                'ndjson' => $this->exportNdjson($query, $output, $total),
            };

            if ($outputPath) {
                fclose($output);
                $this->info("Successfully exported to: {$outputPath}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            if ($outputPath && is_resource($output)) {
                fclose($output);
            }

            $this->error("Export failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Build the query with filters.
     */
    protected function buildQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Trace::query()->with('spans');

        // Date filters
        if ($since = $this->option('since')) {
            $sinceDate = strtotime($since);
            if ($sinceDate === false) {
                $this->error("Invalid since date: {$since}");
                exit(self::FAILURE);
            }
            $query->where('created_at', '>=', date('Y-m-d H:i:s', $sinceDate));
        }

        if ($until = $this->option('until')) {
            $untilDate = strtotime($until);
            if ($untilDate === false) {
                $this->error("Invalid until date: {$until}");
                exit(self::FAILURE);
            }
            $query->where('created_at', '<=', date('Y-m-d H:i:s', $untilDate));
        }

        // Cost filter
        if ($minCost = $this->option('min-cost')) {
            $query->expensive((float) $minCost);
        }

        // Duration filter
        if ($slow = $this->option('slow')) {
            $query->slow((int) $slow);
        }

        // Provider filter
        if ($provider = $this->option('provider')) {
            $query->whereHas('spans', function ($q) use ($provider) {
                $q->provider($provider);
            });
        }

        return $query->orderBy('created_at');
    }

    /**
     * Export traces as CSV.
     */
    protected function exportCsv(\Illuminate\Database\Eloquent\Builder $query, $output, int $total): void
    {
        // Write CSV headers
        fputcsv($output, [
            'trace_id',
            'service_name',
            'start_time',
            'end_time',
            'duration_ms',
            'status',
            'total_spans',
            'total_input_tokens',
            'total_output_tokens',
            'total_tokens',
            'estimated_cost',
            'created_at',
        ]);

        // Stream traces with progress bar
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(100, function ($traces) use ($output, $bar) {
            foreach ($traces as $trace) {
                fputcsv($output, [
                    $trace->trace_id,
                    $trace->service_name,
                    $trace->start_time,
                    $trace->end_time,
                    $trace->getDurationInMilliseconds(),
                    $trace->status,
                    $trace->total_spans,
                    $trace->total_input_tokens,
                    $trace->total_output_tokens,
                    $trace->getTotalTokens(),
                    $trace->estimated_cost,
                    $trace->created_at->toIso8601String(),
                ]);

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    /**
     * Export traces as JSON array.
     */
    protected function exportJson(\Illuminate\Database\Eloquent\Builder $query, $output, int $total): void
    {
        fwrite($output, "[\n");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $first = true;
        $query->chunk(100, function ($traces) use ($output, $bar, &$first) {
            foreach ($traces as $trace) {
                if (! $first) {
                    fwrite($output, ",\n");
                }
                $first = false;

                $data = $this->formatTrace($trace);
                fwrite($output, '  '.json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $bar->advance();
            }
        });

        fwrite($output, "\n]\n");

        $bar->finish();
        $this->newLine();
    }

    /**
     * Export traces as NDJSON (newline-delimited JSON).
     */
    protected function exportNdjson(\Illuminate\Database\Eloquent\Builder $query, $output, int $total): void
    {
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(100, function ($traces) use ($output, $bar) {
            foreach ($traces as $trace) {
                $data = $this->formatTrace($trace);
                fwrite($output, json_encode($data, JSON_UNESCAPED_SLASHES)."\n");

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    /**
     * Format trace data for export.
     */
    protected function formatTrace(Trace $trace): array
    {
        return [
            'trace_id' => $trace->trace_id,
            'service_name' => $trace->service_name,
            'start_time' => $trace->start_time,
            'end_time' => $trace->end_time,
            'duration_ms' => $trace->getDurationInMilliseconds(),
            'status' => $trace->status,
            'total_spans' => $trace->total_spans,
            'total_input_tokens' => $trace->total_input_tokens,
            'total_output_tokens' => $trace->total_output_tokens,
            'total_tokens' => $trace->getTotalTokens(),
            'estimated_cost' => (float) $trace->estimated_cost,
            'metadata' => $trace->metadata,
            'created_at' => $trace->created_at->toIso8601String(),
            'spans' => $trace->spans->map(function ($span) {
                return [
                    'span_id' => $span->span_id,
                    'parent_span_id' => $span->parent_span_id,
                    'name' => $span->name,
                    'kind' => $span->kind,
                    'start_time' => $span->start_time,
                    'end_time' => $span->end_time,
                    'duration_ms' => $span->getDurationInMilliseconds(),
                    'operation_name' => $span->operation_name,
                    'provider_name' => $span->provider_name,
                    'request_model' => $span->request_model,
                    'response_model' => $span->response_model,
                    'input_tokens' => $span->input_tokens,
                    'output_tokens' => $span->output_tokens,
                    'cache_read_tokens' => $span->cache_read_tokens,
                    'cache_creation_tokens' => $span->cache_creation_tokens,
                    'temperature' => $span->temperature,
                    'max_tokens' => $span->max_tokens,
                    'top_p' => $span->top_p,
                    'finish_reasons' => $span->finish_reasons,
                    'status_code' => $span->status_code,
                    'status_description' => $span->status_description,
                ];
            })->toArray(),
        ];
    }
}
