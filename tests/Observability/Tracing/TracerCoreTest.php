<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributes;
use Mindwave\Mindwave\Observability\Tracing\Span;
use Mindwave\Mindwave\Observability\Tracing\SpanBuilder;
use Mindwave\Mindwave\Observability\Tracing\TracerManager;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;

beforeEach(function () {
    $this->exporter = new InMemoryExporter();
    $this->tracer = new TracerManager(
        exporters: [$this->exporter],
        serviceName: 'test-service',
        serviceVersion: '1.0.0',
        instrumentationScope: 'test.scope'
    );
});

test('TracerManager initializes correctly', function () {
    expect($this->tracer)
        ->toBeInstanceOf(TracerManager::class)
        ->and($this->tracer->getServiceName())->toBe('test-service')
        ->and($this->tracer->getServiceVersion())->toBe('1.0.0')
        ->and($this->tracer->getInstrumentationScope())->toBe('test.scope');
});

test('TracerManager can create a basic span', function () {
    $span = $this->tracer->startSpan('test-span');

    expect($span)->toBeInstanceOf(Span::class);

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    expect($spans)->toHaveCount(1)
        ->and($spans[0]->getName())->toBe('test-span');
});

test('TracerManager can create span with attributes', function () {
    $span = $this->tracer->startSpan('test-span', [
        'test.attribute' => 'value',
        'test.number' => 42,
    ]);

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    expect($spans)->toHaveCount(1);

    $attributes = iterator_to_array($spans[0]->getAttributes());
    expect($attributes)
        ->toHaveKey('test.attribute')
        ->toHaveKey('test.number')
        ->and($attributes['test.attribute'])->toBe('value')
        ->and($attributes['test.number'])->toBe(42);
});

test('Span wrapper can set attributes', function () {
    $span = $this->tracer->startSpan('test-span');

    $span->setAttribute('key1', 'value1')
        ->setAttribute('key2', 123)
        ->setAttributes([
            'key3' => 'value3',
            'key4' => 456,
        ]);

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    $attributes = iterator_to_array($spans[0]->getAttributes());

    expect($attributes)
        ->toHaveKey('key1')
        ->toHaveKey('key2')
        ->toHaveKey('key3')
        ->toHaveKey('key4');
});

test('Span can record exceptions', function () {
    $span = $this->tracer->startSpan('test-span');

    $exception = new \RuntimeException('Test exception');
    $span->recordException($exception);

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    expect($spans[0]->getStatus()->getCode())->toBe('Error');
});

test('Span can add events', function () {
    $span = $this->tracer->startSpan('test-span');

    $span->addEvent('test-event', [
        'event.attribute' => 'value',
    ]);

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    $events = $spans[0]->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0]->getName())->toBe('test-event');
});

test('Span can set GenAI operation attributes', function () {
    $span = $this->tracer->startSpan('test-span');

    $span->setGenAiOperation('chat', 'openai', 'gpt-4');

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    $attributes = iterator_to_array($spans[0]->getAttributes());

    expect($attributes)
        ->toHaveKey(GenAiAttributes::GEN_AI_OPERATION_NAME)
        ->toHaveKey(GenAiAttributes::GEN_AI_PROVIDER_NAME)
        ->toHaveKey(GenAiAttributes::GEN_AI_REQUEST_MODEL)
        ->and($attributes[GenAiAttributes::GEN_AI_OPERATION_NAME])->toBe('chat')
        ->and($attributes[GenAiAttributes::GEN_AI_PROVIDER_NAME])->toBe('openai')
        ->and($attributes[GenAiAttributes::GEN_AI_REQUEST_MODEL])->toBe('gpt-4');
});

test('Span can set GenAI request parameters', function () {
    $span = $this->tracer->startSpan('test-span');

    $span->setGenAiRequestParams([
        'temperature' => 0.7,
        'max_tokens' => 100,
        'top_p' => 0.9,
    ]);

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    $attributes = iterator_to_array($spans[0]->getAttributes());

    expect($attributes)
        ->toHaveKey(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE)
        ->toHaveKey(GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS)
        ->toHaveKey(GenAiAttributes::GEN_AI_REQUEST_TOP_P)
        ->and($attributes[GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE])->toBe(0.7)
        ->and($attributes[GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS])->toBe(100)
        ->and($attributes[GenAiAttributes::GEN_AI_REQUEST_TOP_P])->toBe(0.9);
});

test('Span can set GenAI token usage', function () {
    $span = $this->tracer->startSpan('test-span');

    $span->setGenAiUsage(
        inputTokens: 100,
        outputTokens: 50,
        cacheReadTokens: 10,
        cacheCreationTokens: 5
    );

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    $attributes = iterator_to_array($spans[0]->getAttributes());

    expect($attributes)
        ->toHaveKey(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS)
        ->toHaveKey(GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS)
        ->toHaveKey(GenAiAttributes::GEN_AI_USAGE_TOTAL_TOKENS)
        ->toHaveKey(GenAiAttributes::GEN_AI_USAGE_CACHE_READ_TOKENS)
        ->toHaveKey(GenAiAttributes::GEN_AI_USAGE_CACHE_CREATION_TOKENS)
        ->and($attributes[GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS])->toBe(100)
        ->and($attributes[GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS])->toBe(50)
        ->and($attributes[GenAiAttributes::GEN_AI_USAGE_TOTAL_TOKENS])->toBe(150);
});

test('SpanBuilder provides fluent interface', function () {
    $builder = $this->tracer->spanBuilder('test-span');

    expect($builder)->toBeInstanceOf(SpanBuilder::class);

    $span = $builder
        ->asClient()
        ->setAttribute('test.key', 'value')
        ->start();

    expect($span)->toBeInstanceOf(Span::class);

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    expect($spans)->toHaveCount(1)
        ->and($spans[0]->getKind())->toBe(SpanKind::KIND_CLIENT);
});

test('SpanBuilder can create GenAI chat span', function () {
    $span = $this->tracer->spanBuilder('chat gpt-4')
        ->forChat('openai', 'gpt-4')
        ->withGenAiRequestParams([
            'temperature' => 0.7,
            'max_tokens' => 100,
        ])
        ->withServerAttributes('api.openai.com', 443)
        ->start();

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    $attributes = iterator_to_array($spans[0]->getAttributes());

    expect($attributes)
        ->toHaveKey(GenAiAttributes::GEN_AI_OPERATION_NAME)
        ->toHaveKey(GenAiAttributes::GEN_AI_PROVIDER_NAME)
        ->toHaveKey(GenAiAttributes::GEN_AI_REQUEST_MODEL)
        ->toHaveKey(GenAiAttributes::SERVER_ADDRESS)
        ->and($attributes[GenAiAttributes::GEN_AI_OPERATION_NAME])->toBe('chat')
        ->and($attributes[GenAiAttributes::SERVER_ADDRESS])->toBe('api.openai.com');
});

test('Span wrap executes callback with proper context', function () {
    $span = $this->tracer->startSpan('test-span');

    $result = $span->wrap(function () {
        return 'test-result';
    });

    expect($result)->toBe('test-result');

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    expect($spans)->toHaveCount(1);
});

test('Span wrap handles exceptions correctly', function () {
    $span = $this->tracer->startSpan('test-span');

    expect(fn () => $span->wrap(function () {
        throw new \RuntimeException('Test exception');
    }))->toThrow(\RuntimeException::class);

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    expect($spans[0]->getStatus()->getCode())->toBe('Error');
});

test('TracerManager supports multiple exporters', function () {
    $exporter1 = new InMemoryExporter();
    $exporter2 = new InMemoryExporter();

    $tracer = new TracerManager(
        exporters: [$exporter1, $exporter2],
    );

    $span = $tracer->startSpan('test-span');
    $span->end();
    $tracer->forceFlush();

    expect($exporter1->getSpans())->toHaveCount(1)
        ->and($exporter2->getSpans())->toHaveCount(1);
});

test('TracerManager can create custom sampler', function () {
    $sampler = TracerManager::createSampler('always_on');
    expect($sampler)->toBeInstanceOf(\OpenTelemetry\SDK\Trace\SamplerInterface::class);

    $sampler = TracerManager::createSampler('traceidratio', 0.5);
    expect($sampler)->toBeInstanceOf(\OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler::class);
});

test('Span ignores null attributes', function () {
    $span = $this->tracer->startSpan('test-span');

    $span->setAttribute('key1', 'value1')
        ->setAttribute('key2', null)
        ->setAttribute('key3', 'value3');

    $span->end();
    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    $attributes = iterator_to_array($spans[0]->getAttributes());

    expect($attributes)
        ->toHaveKey('key1')
        ->not->toHaveKey('key2')
        ->toHaveKey('key3');
});

test('child spans are properly linked to parent', function () {
    $parentSpan = $this->tracer->startSpan('parent-span');
    $parentScope = $parentSpan->activate();

    $childSpan = $this->tracer->startSpan('child-span');

    $childSpan->end();
    $parentScope->detach();
    $parentSpan->end();

    $this->tracer->forceFlush();

    $spans = $this->exporter->getSpans();
    expect($spans)->toHaveCount(2);

    $childSpanData = $spans[0];
    $parentSpanData = $spans[1];

    expect($childSpanData->getParentContext()->getSpanId())
        ->toBe($parentSpanData->getContext()->getSpanId());
});
