<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mindwave\Mindwave\Observability\Models\Span;
use Mindwave\Mindwave\Observability\Models\Trace;

class TraceStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mindwave:trace-stats
                            {--since= : Show statistics from this date (e.g., yesterday, 2024-01-01)}
                            {--provider= : Filter by provider name}
                            {--model= : Filter by model name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display trace statistics and analytics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Mindwave Trace Statistics');
        $this->newLine();

        // Build queries with filters
        $traceQuery = $this->buildTraceQuery();
        $spanQuery = $this->buildSpanQuery();

        // Overall statistics
        $this->displayOverallStats($traceQuery, $spanQuery);

        // Token usage
        $this->newLine();
        $this->displayTokenStats($traceQuery);

        // Cost analysis
        $this->newLine();
        $this->displayCostStats($traceQuery);

        // Performance metrics
        $this->newLine();
        $this->displayPerformanceStats($traceQuery);

        // Top models by usage
        $this->newLine();
        $this->displayTopModelsByUsage($spanQuery);

        // Top models by cost
        $this->newLine();
        $this->displayTopModelsByCost($traceQuery);

        // Error analysis
        $this->newLine();
        $this->displayErrorStats($traceQuery);

        return self::SUCCESS;
    }

    /**
     * Build trace query with filters.
     */
    protected function buildTraceQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Trace::query();

        if ($since = $this->option('since')) {
            $sinceDate = strtotime($since);
            if ($sinceDate === false) {
                $this->error("Invalid since date: {$since}");
                exit(self::FAILURE);
            }
            $query->where('created_at', '>=', date('Y-m-d H:i:s', $sinceDate));
        }

        if ($provider = $this->option('provider')) {
            $query->whereHas('spans', function ($q) use ($provider) {
                $q->provider($provider);
            });
        }

        if ($model = $this->option('model')) {
            $query->whereHas('spans', function ($q) use ($model) {
                $q->model($model);
            });
        }

        return $query;
    }

    /**
     * Build span query with filters.
     */
    protected function buildSpanQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Span::query();

        if ($since = $this->option('since')) {
            $sinceDate = strtotime($since);
            $query->where('created_at', '>=', date('Y-m-d H:i:s', $sinceDate));
        }

        if ($provider = $this->option('provider')) {
            $query->provider($provider);
        }

        if ($model = $this->option('model')) {
            $query->model($model);
        }

        return $query;
    }

    /**
     * Display overall statistics.
     */
    protected function displayOverallStats($traceQuery, $spanQuery): void
    {
        $totalTraces = $traceQuery->count();
        $totalSpans = $spanQuery->count();
        $completedTraces = $traceQuery->whereNotNull('end_time')->count();
        $avgSpansPerTrace = $totalTraces > 0 ? round($totalSpans / $totalTraces, 2) : 0;

        $this->info('Overall Statistics');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Traces', number_format($totalTraces)],
                ['Total Spans', number_format($totalSpans)],
                ['Completed Traces', number_format($completedTraces)],
                ['Avg Spans per Trace', $avgSpansPerTrace],
            ]
        );
    }

    /**
     * Display token usage statistics.
     */
    protected function displayTokenStats($query): void
    {
        $stats = $query->selectRaw('
            SUM(total_input_tokens) as total_input,
            SUM(total_output_tokens) as total_output,
            AVG(total_input_tokens) as avg_input,
            AVG(total_output_tokens) as avg_output
        ')->first();

        $totalTokens = ($stats->total_input ?? 0) + ($stats->total_output ?? 0);

        $this->info('Token Usage');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Input Tokens', number_format($stats->total_input ?? 0)],
                ['Total Output Tokens', number_format($stats->total_output ?? 0)],
                ['Total Tokens', number_format($totalTokens)],
                ['Avg Input Tokens', number_format($stats->avg_input ?? 0, 2)],
                ['Avg Output Tokens', number_format($stats->avg_output ?? 0, 2)],
            ]
        );

        // Token usage bar chart
        if ($stats->total_input > 0 || $stats->total_output > 0) {
            $this->newLine();
            $this->displayTokenChart($stats->total_input, $stats->total_output);
        }
    }

    /**
     * Display cost statistics.
     */
    protected function displayCostStats($query): void
    {
        $stats = $query->selectRaw('
            SUM(estimated_cost) as total_cost,
            AVG(estimated_cost) as avg_cost,
            MIN(estimated_cost) as min_cost,
            MAX(estimated_cost) as max_cost
        ')->first();

        $this->info('Cost Analysis');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cost', '$'.number_format($stats->total_cost ?? 0, 4)],
                ['Average Cost', '$'.number_format($stats->avg_cost ?? 0, 4)],
                ['Min Cost', '$'.number_format($stats->min_cost ?? 0, 4)],
                ['Max Cost', '$'.number_format($stats->max_cost ?? 0, 4)],
            ]
        );
    }

    /**
     * Display performance statistics.
     */
    protected function displayPerformanceStats($query): void
    {
        $stats = $query
            ->whereNotNull('duration')
            ->selectRaw('
                AVG(duration) as avg_duration,
                MIN(duration) as min_duration,
                MAX(duration) as max_duration
            ')
            ->first();

        if (! $stats || $stats->avg_duration === null) {
            return;
        }

        $avgMs = round($stats->avg_duration / 1_000_000, 2);
        $minMs = round($stats->min_duration / 1_000_000, 2);
        $maxMs = round($stats->max_duration / 1_000_000, 2);

        $this->info('Performance Metrics');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Avg Duration', $avgMs.' ms'],
                ['Min Duration', $minMs.' ms'],
                ['Max Duration', $maxMs.' ms'],
            ]
        );
    }

    /**
     * Display top models by usage.
     */
    protected function displayTopModelsByUsage($spanQuery): void
    {
        $topModels = $spanQuery
            ->select('request_model')
            ->selectRaw('COUNT(*) as usage_count')
            ->selectRaw('SUM(input_tokens + output_tokens) as total_tokens')
            ->whereNotNull('request_model')
            ->groupBy('request_model')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        if ($topModels->isEmpty()) {
            return;
        }

        $this->info('Top Models by Usage');
        $this->table(
            ['Model', 'Uses', 'Total Tokens', 'Chart'],
            $topModels->map(function ($model) use ($topModels) {
                $maxUsage = $topModels->max('usage_count');
                $barLength = $maxUsage > 0 ? round(($model->usage_count / $maxUsage) * 30) : 0;

                return [
                    $model->request_model,
                    number_format($model->usage_count),
                    number_format($model->total_tokens ?? 0),
                    str_repeat('▓', $barLength),
                ];
            })->toArray()
        );
    }

    /**
     * Display top models by cost.
     */
    protected function displayTopModelsByCost($traceQuery): void
    {
        // Get top models by aggregating from spans
        $topModels = DB::table('mindwave_spans')
            ->select('request_model')
            ->selectRaw('COUNT(*) as usage_count')
            ->join('mindwave_traces', 'mindwave_spans.trace_id', '=', 'mindwave_traces.trace_id')
            ->selectRaw('SUM(mindwave_traces.estimated_cost) / COUNT(DISTINCT mindwave_traces.trace_id) as avg_cost')
            ->whereNotNull('request_model')
            ->groupBy('request_model')
            ->orderByDesc('avg_cost')
            ->limit(10)
            ->get();

        if ($topModels->isEmpty()) {
            return;
        }

        $this->info('Top Models by Cost');
        $this->table(
            ['Model', 'Uses', 'Avg Cost', 'Chart'],
            $topModels->map(function ($model) use ($topModels) {
                $maxCost = $topModels->max('avg_cost');
                $barLength = $maxCost > 0 ? round(($model->avg_cost / $maxCost) * 30) : 0;

                return [
                    $model->request_model,
                    number_format($model->usage_count),
                    '$'.number_format($model->avg_cost, 4),
                    str_repeat('▓', $barLength),
                ];
            })->toArray()
        );
    }

    /**
     * Display error statistics.
     */
    protected function displayErrorStats($query): void
    {
        $total = $query->count();
        $errors = $query->where('status', 'error')->count();
        $errorRate = $total > 0 ? round(($errors / $total) * 100, 2) : 0;

        $this->info('Error Analysis');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Errors', number_format($errors)],
                ['Error Rate', $errorRate.'%'],
            ]
        );

        if ($errors > 0) {
            // Show error rate visualization
            $successRate = 100 - $errorRate;
            $successBars = round($successRate / 2);
            $errorBars = round($errorRate / 2);

            $this->newLine();
            $this->line('Success vs Errors:');
            $this->line('<info>'.str_repeat('▓', $successBars).'</info><error>'.str_repeat('▓', $errorBars).'</error>');
            $this->line('<info>Success: '.$successRate.'%</info> | <error>Errors: '.$errorRate.'%</error>');
        }
    }

    /**
     * Display token usage chart.
     */
    protected function displayTokenChart($inputTokens, $outputTokens): void
    {
        $total = $inputTokens + $outputTokens;
        if ($total === 0) {
            return;
        }

        $inputPercent = round(($inputTokens / $total) * 100, 1);
        $outputPercent = round(($outputTokens / $total) * 100, 1);

        $inputBars = round($inputPercent / 2);
        $outputBars = round($outputPercent / 2);

        $this->line('Token Distribution:');
        $this->line('<comment>'.str_repeat('▓', $inputBars).'</comment><info>'.str_repeat('▓', $outputBars).'</info>');
        $this->line('<comment>Input: '.$inputPercent.'%</comment> | <info>Output: '.$outputPercent.'%</info>');
    }
}
