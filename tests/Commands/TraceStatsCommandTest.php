<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mindwave\Mindwave\Commands\TraceStatsCommand;
use Mindwave\Mindwave\Observability\Models\Span;
use Mindwave\Mindwave\Observability\Models\Trace;

uses(RefreshDatabase::class);

describe('TraceStatsCommand', function () {
    describe('Basic output', function () {
        it('displays header', function () {
            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('Mindwave Trace Statistics')
                ->assertExitCode(0);
        });

        it('shows overall statistics section', function () {
            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('Overall Statistics')
                ->expectsOutputToContain('Total Traces')
                ->assertExitCode(0);
        });

        it('shows token usage section', function () {
            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('Token Usage')
                ->assertExitCode(0);
        });

        it('shows cost analysis section', function () {
            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('Cost Analysis')
                ->assertExitCode(0);
        });

        it('shows error analysis section', function () {
            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('Error Analysis')
                ->assertExitCode(0);
        });
    });

    describe('Overall statistics', function () {
        it('counts traces correctly', function () {
            createStatsTrace('trace1');
            createStatsTrace('trace2');
            createStatsTrace('trace3');

            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('3')
                ->assertExitCode(0);
        });

        it('counts spans correctly', function () {
            $trace = createStatsTrace('trace-with-spans');
            createStatsSpan($trace, 'span1');
            createStatsSpan($trace, 'span2');

            $this->artisan(TraceStatsCommand::class)
                ->assertExitCode(0);
        });

        it('shows zero when no traces', function () {
            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('0')
                ->assertExitCode(0);
        });
    });

    describe('Token statistics', function () {
        it('aggregates input tokens', function () {
            createStatsTrace('t1', ['total_input_tokens' => 100]);
            createStatsTrace('t2', ['total_input_tokens' => 200]);

            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('300')
                ->assertExitCode(0);
        });

        it('aggregates output tokens', function () {
            createStatsTrace('t1', ['total_output_tokens' => 50]);
            createStatsTrace('t2', ['total_output_tokens' => 150]);

            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('200')
                ->assertExitCode(0);
        });
    });

    describe('Cost statistics', function () {
        it('shows total cost', function () {
            createStatsTrace('t1', ['estimated_cost' => 0.01]);
            createStatsTrace('t2', ['estimated_cost' => 0.02]);

            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('$0.03')
                ->assertExitCode(0);
        });
    });

    describe('Error statistics', function () {
        it('counts error traces', function () {
            createStatsTrace('ok1', ['status' => 'ok']);
            createStatsTrace('ok2', ['status' => 'ok']);
            createStatsTrace('err', ['status' => 'error']);

            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('Total Errors')
                ->assertExitCode(0);
        });
    });

    describe('Filtering', function () {
        it('filters by since date', function () {
            createStatsTrace('old', ['created_at' => now()->subDays(10)]);
            createStatsTrace('recent', ['created_at' => now()]);

            $this->artisan(TraceStatsCommand::class, [
                '--since' => now()->subDays(5)->toDateString(),
            ])->assertExitCode(0);
        });

        it('filters by provider', function () {
            $trace1 = createStatsTrace('openai-trace');
            createStatsSpan($trace1, 'span1', ['provider_name' => 'openai']);

            $trace2 = createStatsTrace('anthropic-trace');
            createStatsSpan($trace2, 'span2', ['provider_name' => 'anthropic']);

            $this->artisan(TraceStatsCommand::class, [
                '--provider' => 'openai',
            ])->assertExitCode(0);
        });

        it('filters by model', function () {
            $trace1 = createStatsTrace('gpt4-trace');
            createStatsSpan($trace1, 'span1', ['request_model' => 'gpt-4']);

            $trace2 = createStatsTrace('claude-trace');
            createStatsSpan($trace2, 'span2', ['request_model' => 'claude-3']);

            $this->artisan(TraceStatsCommand::class, [
                '--model' => 'gpt-4',
            ])->assertExitCode(0);
        });
    });

    describe('Top models', function () {
        it('displays top models by usage', function () {
            $trace1 = createStatsTrace('trace1');
            createStatsSpan($trace1, 'span1', ['request_model' => 'gpt-4']);
            createStatsSpan($trace1, 'span2', ['request_model' => 'gpt-4']);

            $trace2 = createStatsTrace('trace2');
            createStatsSpan($trace2, 'span3', ['request_model' => 'claude-3']);

            $this->artisan(TraceStatsCommand::class)
                ->expectsOutputToContain('Top Models by Usage')
                ->assertExitCode(0);
        });
    });
});

// Helper functions
function createStatsTrace(string $traceIdPrefix, array $overrides = []): Trace
{
    $traceId = str_pad($traceIdPrefix, 32, '0');

    return Trace::create(array_merge([
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
}

function createStatsSpan(Trace $trace, string $spanIdPrefix, array $overrides = []): Span
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
