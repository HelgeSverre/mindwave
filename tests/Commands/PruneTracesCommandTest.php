<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mindwave\Mindwave\Commands\PruneTracesCommand;
use Mindwave\Mindwave\Observability\Models\Span;
use Mindwave\Mindwave\Observability\Models\Trace;

uses(RefreshDatabase::class);

describe('PruneTracesCommand', function () {
    describe('Option validation', function () {
        it('rejects invalid older-than value', function () {
            $this->artisan(PruneTracesCommand::class, ['--older-than' => '0'])
                ->expectsOutput('The --older-than option must be a positive integer.')
                ->assertExitCode(1);
        });

        it('rejects negative older-than value', function () {
            $this->artisan(PruneTracesCommand::class, ['--older-than' => '-5'])
                ->expectsOutput('The --older-than option must be a positive integer.')
                ->assertExitCode(1);
        });

        it('rejects invalid batch-size value', function () {
            $this->artisan(PruneTracesCommand::class, ['--batch-size' => '0'])
                ->expectsOutput('The --batch-size option must be a positive integer.')
                ->assertExitCode(1);
        });
    });

    describe('Empty database', function () {
        it('reports no traces to delete', function () {
            $this->artisan(PruneTracesCommand::class, ['--force' => true])
                ->expectsOutput('No traces found to delete.')
                ->assertExitCode(0);
        });
    });

    describe('Dry run', function () {
        it('shows what would be deleted without deleting', function () {
            createPruneTrace('old', ['created_at' => now()->subDays(60)]);

            $this->artisan(PruneTracesCommand::class, ['--dry-run' => true])
                ->expectsOutputToContain('DRY RUN - No data will be deleted')
                ->assertExitCode(0);

            expect(Trace::count())->toBe(1);
        });

        it('displays sample traces in dry run', function () {
            createPruneTrace('old1', ['created_at' => now()->subDays(60)]);

            $this->artisan(PruneTracesCommand::class, ['--dry-run' => true])
                ->expectsOutputToContain('Sample traces to be deleted')
                ->assertExitCode(0);
        });
    });

    describe('Deletion', function () {
        it('deletes traces older than specified days', function () {
            createPruneTrace('old', ['created_at' => now()->subDays(60)]);
            createPruneTrace('recent', ['created_at' => now()]);

            $this->artisan(PruneTracesCommand::class, [
                '--older-than' => '30',
                '--force' => true,
            ])->assertExitCode(0);

            expect(Trace::count())->toBe(1);
            expect(Trace::first()->trace_id)->toBe(str_pad('recent', 32, '0'));
        });

        it('cascades deletion to spans', function () {
            $trace = createPruneTrace('old', ['created_at' => now()->subDays(60)]);
            createPruneSpan($trace, 'span1');
            createPruneSpan($trace, 'span2');

            expect(Span::count())->toBe(2);

            $this->artisan(PruneTracesCommand::class, [
                '--older-than' => '30',
                '--force' => true,
            ])->assertExitCode(0);

            expect(Trace::count())->toBe(0);
            expect(Span::count())->toBe(0);
        });

        it('respects keep-errors flag', function () {
            createPruneTrace('old-ok', ['created_at' => now()->subDays(60), 'status' => 'ok']);
            createPruneTrace('old-error', ['created_at' => now()->subDays(60), 'status' => 'error']);

            $this->artisan(PruneTracesCommand::class, [
                '--older-than' => '30',
                '--keep-errors' => true,
                '--force' => true,
            ])->assertExitCode(0);

            expect(Trace::count())->toBe(1);
            expect(Trace::first()->status)->toBe('error');
        });
    });

    describe('Confirmation', function () {
        it('asks for confirmation without force flag', function () {
            createPruneTrace('old', ['created_at' => now()->subDays(60)]);

            $this->artisan(PruneTracesCommand::class)
                ->expectsConfirmation('Delete 1 traces and 0 spans?', 'no')
                ->expectsOutput('Pruning cancelled.')
                ->assertExitCode(0);

            expect(Trace::count())->toBe(1);
        });

        it('proceeds when confirmation is yes', function () {
            createPruneTrace('old', ['created_at' => now()->subDays(60)]);

            $this->artisan(PruneTracesCommand::class)
                ->expectsConfirmation('Delete 1 traces and 0 spans?', 'yes')
                ->assertExitCode(0);

            expect(Trace::count())->toBe(0);
        });

        it('skips confirmation with force flag', function () {
            createPruneTrace('old', ['created_at' => now()->subDays(60)]);

            $this->artisan(PruneTracesCommand::class, ['--force' => true])
                ->assertExitCode(0);

            expect(Trace::count())->toBe(0);
        });
    });

    describe('Statistics display', function () {
        it('shows count of traces to delete', function () {
            createPruneTrace('old1', ['created_at' => now()->subDays(60)]);
            createPruneTrace('old2', ['created_at' => now()->subDays(60)]);

            $this->artisan(PruneTracesCommand::class, ['--dry-run' => true])
                ->expectsOutputToContain('Traces to delete')
                ->expectsOutputToContain('2')
                ->assertExitCode(0);
        });

        it('shows count of related spans to delete', function () {
            $trace = createPruneTrace('old', ['created_at' => now()->subDays(60)]);
            createPruneSpan($trace, 'span1');
            createPruneSpan($trace, 'span2');
            createPruneSpan($trace, 'span3');

            $this->artisan(PruneTracesCommand::class, ['--dry-run' => true])
                ->expectsOutputToContain('Related spans to delete')
                ->expectsOutputToContain('3')
                ->assertExitCode(0);
        });
    });

    describe('Batch processing', function () {
        it('processes in batches', function () {
            // Create 10 old traces
            for ($i = 0; $i < 10; $i++) {
                createPruneTrace("old{$i}", ['created_at' => now()->subDays(60)]);
            }

            $this->artisan(PruneTracesCommand::class, [
                '--older-than' => '30',
                '--batch-size' => '3',
                '--force' => true,
            ])->assertExitCode(0);

            expect(Trace::count())->toBe(0);
        });
    });
});

// Helper functions
function createPruneTrace(string $traceIdPrefix, array $overrides = []): Trace
{
    $traceId = str_pad($traceIdPrefix, 32, '0');

    // Extract created_at before merge since it needs special handling
    $createdAt = $overrides['created_at'] ?? now();
    unset($overrides['created_at']);

    $trace = new Trace(array_merge([
        'trace_id' => $traceId,
        'service_name' => 'test-service',
        'start_time' => now()->timestamp * 1_000_000_000,
        'end_time' => now()->timestamp * 1_000_000_000 + 1_000_000_000,
        'duration' => 1_000_000_000,
        'status' => 'ok',
        'total_spans' => 0,
        'total_input_tokens' => 100,
        'total_output_tokens' => 50,
        'estimated_cost' => 0.01,
    ], $overrides));

    // Manually set created_at since it's not fillable
    $trace->created_at = $createdAt;
    $trace->save();

    return $trace;
}

function createPruneSpan(Trace $trace, string $spanIdPrefix, array $overrides = []): Span
{
    $spanId = str_pad($spanIdPrefix, 16, '0');

    return Span::create(array_merge([
        'trace_id' => $trace->trace_id,
        'span_id' => $spanId,
        'name' => 'test-span',
        'kind' => 'client',
        'start_time' => now()->timestamp * 1_000_000_000,
        'end_time' => now()->timestamp * 1_000_000_000 + 500_000_000,
        'duration' => 500_000_000,
        'operation_name' => 'chat',
        'provider_name' => 'openai',
        'request_model' => 'gpt-4',
        'input_tokens' => 100,
        'output_tokens' => 50,
        'status_code' => 'ok',
        'created_at' => now(),
    ], $overrides));
}
