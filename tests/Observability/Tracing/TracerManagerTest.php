<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Tracing\Span;
use Mindwave\Mindwave\Observability\Tracing\SpanBuilder;
use Mindwave\Mindwave\Observability\Tracing\TracerManager;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;

describe('TracerManager', function () {
    describe('Initialization', function () {
        it('creates manager with default settings', function () {
            $manager = new TracerManager;

            expect($manager->getServiceName())->toBe('mindwave');
            expect($manager->getServiceVersion())->toBe('1.0.0');
            expect($manager->getInstrumentationScope())->toBe('mindwave.llm');
        });

        it('creates manager with custom settings', function () {
            $manager = new TracerManager(
                exporters: [],
                serviceName: 'my-service',
                serviceVersion: '2.0.0',
                instrumentationScope: 'custom.scope'
            );

            expect($manager->getServiceName())->toBe('my-service');
            expect($manager->getServiceVersion())->toBe('2.0.0');
            expect($manager->getInstrumentationScope())->toBe('custom.scope');
        });

        it('returns tracer instance', function () {
            $manager = new TracerManager;

            expect($manager->getTracer())->toBeInstanceOf(TracerInterface::class);
        });

        it('returns tracer provider instance', function () {
            $manager = new TracerManager;

            expect($manager->getTracerProvider())->toBeInstanceOf(TracerProviderInterface::class);
        });
    });

    describe('Span Creation', function () {
        it('creates span with name', function () {
            $manager = new TracerManager;

            $span = $manager->startSpan('test-span');

            expect($span)->toBeInstanceOf(Span::class);
            expect($span->isRecording())->toBeTrue();

            $span->end();
        });

        it('creates span with attributes', function () {
            $manager = new TracerManager;

            $span = $manager->startSpan('test-span', [
                'key1' => 'value1',
                'key2' => 123,
            ]);

            expect($span)->toBeInstanceOf(Span::class);

            $span->end();
        });

        it('creates span with custom kind', function () {
            $manager = new TracerManager;

            $span = $manager->startSpan('test-span', [], SpanKind::KIND_SERVER);

            expect($span)->toBeInstanceOf(Span::class);

            $span->end();
        });

        it('skips null attribute values', function () {
            $manager = new TracerManager;

            $span = $manager->startSpan('test-span', [
                'valid' => 'value',
                'null' => null,
            ]);

            expect($span)->toBeInstanceOf(Span::class);

            $span->end();
        });

        it('creates span builder for fine-grained control', function () {
            $manager = new TracerManager;

            $builder = $manager->spanBuilder('test-span');

            expect($builder)->toBeInstanceOf(SpanBuilder::class);
        });
    });

    describe('Exporter Management', function () {
        it('starts with empty exporters', function () {
            $manager = new TracerManager;

            expect($manager->getExporters())->toBe([]);
        });

        it('adds exporter', function () {
            $manager = new TracerManager;
            $exporter = Mockery::mock(SpanExporterInterface::class);

            $manager->addExporter($exporter);

            expect($manager->getExporters())->toHaveCount(1);
        });

        it('initializes with provided exporters', function () {
            $exporter = Mockery::mock(SpanExporterInterface::class);
            $exporter->shouldReceive('shutdown')->andReturn(true);
            $exporter->shouldReceive('forceFlush')->andReturn(true);

            $manager = new TracerManager(exporters: [$exporter]);

            expect($manager->getExporters())->toHaveCount(1);
        });
    });

    describe('Sampler Creation', function () {
        it('creates always_on sampler', function () {
            $sampler = TracerManager::createSampler('always_on');

            expect($sampler)->toBeInstanceOf(AlwaysOnSampler::class);
        });

        it('creates always_off sampler', function () {
            $sampler = TracerManager::createSampler('always_off');

            expect($sampler)->toBeInstanceOf(AlwaysOffSampler::class);
        });

        it('creates traceidratio sampler', function () {
            $sampler = TracerManager::createSampler('traceidratio', 0.5);

            expect($sampler)->toBeInstanceOf(TraceIdRatioBasedSampler::class);
        });

        it('creates parentbased sampler', function () {
            $sampler = TracerManager::createSampler('parentbased');

            expect($sampler)->toBeInstanceOf(ParentBased::class);
        });

        it('defaults to always_on for unknown type', function () {
            $sampler = TracerManager::createSampler('unknown');

            expect($sampler)->toBeInstanceOf(AlwaysOnSampler::class);
        });
    });

    describe('Lifecycle Management', function () {
        it('force flushes spans', function () {
            $manager = new TracerManager;

            $result = $manager->forceFlush();

            expect($result)->toBeTrue();
        });

        it('shuts down cleanly', function () {
            $manager = new TracerManager;

            $result = $manager->shutdown();

            expect($result)->toBeTrue();
        });
    });
});
