<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Command;
use Mindwave\Mindwave\Observability\Models\Trace;

class PruneTracesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mindwave:prune-traces
                            {--older-than=30 : Delete traces older than this many days}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--keep-errors : Keep traces with error status}
                            {--batch-size=500 : Number of traces to delete per batch}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old traces from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $olderThan = (int) $this->option('older-than');
        $dryRun = $this->option('dry-run');
        $keepErrors = $this->option('keep-errors');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        if ($olderThan <= 0) {
            $this->error('The --older-than option must be a positive integer.');

            return self::FAILURE;
        }

        if ($batchSize <= 0) {
            $this->error('The --batch-size option must be a positive integer.');

            return self::FAILURE;
        }

        // Calculate cutoff date
        $cutoffDate = now()->subDays($olderThan);

        // Build query
        $query = Trace::query()
            ->where('created_at', '<', $cutoffDate);

        if ($keepErrors) {
            $query->where('status', '!=', 'error');
        }

        // Get counts
        $totalToDelete = $query->count();
        $totalSpans = $this->countSpans($query);

        if ($totalToDelete === 0) {
            $this->info('No traces found to delete.');

            return self::SUCCESS;
        }

        // Display information
        $this->info("Traces older than {$olderThan} days (before {$cutoffDate->toDateString()}):");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Traces to delete', number_format($totalToDelete)],
                ['Related spans to delete', number_format($totalSpans)],
            ]
        );

        if ($keepErrors) {
            $this->warn('Traces with errors will be kept.');
        }

        // Dry run
        if ($dryRun) {
            $this->warn('DRY RUN - No data will be deleted.');
            $this->displaySampleTraces($query);

            return self::SUCCESS;
        }

        // Confirmation
        if (! $force) {
            if (! $this->confirm("Delete {$totalToDelete} traces and {$totalSpans} spans?", false)) {
                $this->info('Pruning cancelled.');

                return self::SUCCESS;
            }
        }

        // Delete in batches
        $this->info('Deleting traces...');
        $bar = $this->output->createProgressBar($totalToDelete);
        $bar->start();

        $deleted = 0;
        do {
            $batch = $query->limit($batchSize)->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $trace) {
                // Delete spans first (cascade)
                $trace->spans()->delete();
                // Delete trace
                $trace->delete();

                $deleted++;
                $bar->advance();
            }
        } while ($batch->count() === $batchSize);

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Successfully deleted {$deleted} traces and their related spans.");

        return self::SUCCESS;
    }

    /**
     * Count total spans for traces to be deleted.
     */
    protected function countSpans(\Illuminate\Database\Eloquent\Builder $query): int
    {
        $traceIds = $query->pluck('trace_id');

        return \Mindwave\Mindwave\Observability\Models\Span::query()
            ->whereIn('trace_id', $traceIds)
            ->count();
    }

    /**
     * Display sample traces that would be deleted.
     */
    protected function displaySampleTraces(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $samples = $query->limit(5)->get();

        if ($samples->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->info('Sample traces to be deleted:');

        $this->table(
            ['Trace ID', 'Service', 'Created At', 'Status', 'Spans', 'Cost'],
            $samples->map(fn ($trace) => [
                substr($trace->trace_id, 0, 16).'...',
                $trace->service_name,
                $trace->created_at->toDateString(),
                $trace->status,
                $trace->total_spans,
                '$'.number_format($trace->estimated_cost, 4),
            ])->toArray()
        );
    }
}
