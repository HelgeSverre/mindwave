<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mindwave\Mindwave\Commands\ExportTracesCommand;
use Mindwave\Mindwave\Observability\Models\Span;
use Mindwave\Mindwave\Observability\Models\Trace;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/mindwave-export-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    if (isset($this->tempDir) && is_dir($this->tempDir)) {
        array_map('unlink', glob($this->tempDir.'/*'));
        rmdir($this->tempDir);
    }
});

describe('ExportTracesCommand', function () {
    describe('Format validation', function () {
        it('rejects invalid format', function () {
            $this->artisan(ExportTracesCommand::class, ['--format' => 'xml'])
                ->expectsOutput('Invalid format: xml. Must be csv, json, or ndjson.')
                ->assertExitCode(1);
        });

        it('accepts csv format', function () {
            $this->artisan(ExportTracesCommand::class, ['--format' => 'csv'])
                ->expectsOutput('No traces found matching the criteria.')
                ->assertExitCode(0);
        });

        it('accepts json format', function () {
            $this->artisan(ExportTracesCommand::class, ['--format' => 'json'])
                ->expectsOutput('No traces found matching the criteria.')
                ->assertExitCode(0);
        });

        it('accepts ndjson format', function () {
            $this->artisan(ExportTracesCommand::class, ['--format' => 'ndjson'])
                ->expectsOutput('No traces found matching the criteria.')
                ->assertExitCode(0);
        });
    });

    describe('Empty results', function () {
        it('warns when no traces found', function () {
            $this->artisan(ExportTracesCommand::class)
                ->expectsOutput('No traces found matching the criteria.')
                ->assertExitCode(0);
        });
    });

    describe('CSV export', function () {
        it('exports traces to CSV file', function () {
            createTestTrace('trace1');

            $outputFile = $this->tempDir.'/export.csv';

            $this->artisan(ExportTracesCommand::class, [
                '--format' => 'csv',
                '--output' => $outputFile,
            ])->assertExitCode(0);

            expect(file_exists($outputFile))->toBeTrue();

            $contents = file_get_contents($outputFile);
            expect($contents)->toContain('trace_id');
            expect($contents)->toContain('service_name');
            expect($contents)->toContain('trace1');
        });

        it('includes CSV headers', function () {
            createTestTrace('trace-header');

            $outputFile = $this->tempDir.'/headers.csv';

            $this->artisan(ExportTracesCommand::class, [
                '--format' => 'csv',
                '--output' => $outputFile,
            ])->assertExitCode(0);

            $lines = file($outputFile);
            $headers = str_getcsv($lines[0]);

            expect($headers)->toContain('trace_id');
            expect($headers)->toContain('service_name');
            expect($headers)->toContain('duration_ms');
            expect($headers)->toContain('estimated_cost');
        });
    });

    describe('JSON export', function () {
        it('exports traces to JSON file', function () {
            createTestTrace('json-trace');

            $outputFile = $this->tempDir.'/export.json';

            $this->artisan(ExportTracesCommand::class, [
                '--format' => 'json',
                '--output' => $outputFile,
            ])->assertExitCode(0);

            expect(file_exists($outputFile))->toBeTrue();

            $data = json_decode(file_get_contents($outputFile), true);
            expect($data)->toBeArray();
            expect($data)->toHaveCount(1);
            expect($data[0]['trace_id'])->toBe('json-trace'.str_repeat('0', 22));
        });

        it('includes spans in JSON export', function () {
            $trace = createTestTrace('with-spans');
            createTestSpan($trace, 'span1');

            $outputFile = $this->tempDir.'/with-spans.json';

            $this->artisan(ExportTracesCommand::class, [
                '--format' => 'json',
                '--output' => $outputFile,
            ])->assertExitCode(0);

            $data = json_decode(file_get_contents($outputFile), true);
            expect($data[0]['spans'])->toBeArray();
            expect($data[0]['spans'])->toHaveCount(1);
        });
    });

    describe('NDJSON export', function () {
        it('exports traces to NDJSON file', function () {
            createTestTrace('ndjson-1');
            createTestTrace('ndjson-2');

            $outputFile = $this->tempDir.'/export.ndjson';

            $this->artisan(ExportTracesCommand::class, [
                '--format' => 'ndjson',
                '--output' => $outputFile,
            ])->assertExitCode(0);

            $lines = array_filter(file($outputFile, FILE_IGNORE_NEW_LINES));
            expect($lines)->toHaveCount(2);

            // Each line should be valid JSON
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                expect($decoded)->toBeArray();
                expect($decoded)->toHaveKey('trace_id');
            }
        });
    });

    describe('Filtering', function () {
        it('filters by min-cost', function () {
            createTestTrace('cheap', ['estimated_cost' => 0.001]);
            createTestTrace('expensive', ['estimated_cost' => 1.0]);

            $outputFile = $this->tempDir.'/expensive.json';

            $this->artisan(ExportTracesCommand::class, [
                '--format' => 'json',
                '--output' => $outputFile,
                '--min-cost' => '0.5',
            ])->assertExitCode(0);

            $data = json_decode(file_get_contents($outputFile), true);
            expect($data)->toHaveCount(1);
            expect($data[0]['estimated_cost'])->toBeGreaterThan(0.5);
        });

        it('filters by slow duration', function () {
            createTestTrace('fast', ['duration' => 100_000_000]); // 100ms
            createTestTrace('slow', ['duration' => 10_000_000_000]); // 10s

            $outputFile = $this->tempDir.'/slow.json';

            $this->artisan(ExportTracesCommand::class, [
                '--format' => 'json',
                '--output' => $outputFile,
                '--slow' => '5000', // 5000ms = 5s
            ])->assertExitCode(0);

            $data = json_decode(file_get_contents($outputFile), true);
            expect($data)->toHaveCount(1);
        });

        it('filters by date range with since', function () {
            createTestTrace('old', ['created_at' => now()->subDays(10)]);
            createTestTrace('recent', ['created_at' => now()]);

            $outputFile = $this->tempDir.'/recent.json';

            $this->artisan(ExportTracesCommand::class, [
                '--format' => 'json',
                '--output' => $outputFile,
                '--since' => now()->subDays(5)->toDateString(),
            ])->assertExitCode(0);

            $data = json_decode(file_get_contents($outputFile), true);
            expect($data)->toHaveCount(1);
        });
    });
});

// Helper functions
function createTestTrace(string $traceIdPrefix, array $overrides = []): Trace
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

function createTestSpan(Trace $trace, string $spanIdPrefix, array $overrides = []): Span
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
