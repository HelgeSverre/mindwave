<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Tracing\Span;
use Mindwave\Mindwave\Observability\Tracing\SpanBuilder;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\ContextInterface;

beforeEach(function () {
    $this->mockBuilder = Mockery::mock(SpanBuilderInterface::class)->makePartial();
    $this->spanBuilder = new SpanBuilder($this->mockBuilder);
});

afterEach(function () {
    Mockery::close();
});

describe('SpanBuilder', function () {
    describe('Span Kind', function () {
        it('sets span kind', function () {
            $this->mockBuilder->shouldReceive('setSpanKind')->with(SpanKind::KIND_CLIENT)->once();

            $result = $this->spanBuilder->setSpanKind(SpanKind::KIND_CLIENT);

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets as client', function () {
            $this->mockBuilder->shouldReceive('setSpanKind')->with(SpanKind::KIND_CLIENT)->once();

            $result = $this->spanBuilder->asClient();

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets as server', function () {
            $this->mockBuilder->shouldReceive('setSpanKind')->with(SpanKind::KIND_SERVER)->once();

            $result = $this->spanBuilder->asServer();

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets as internal', function () {
            $this->mockBuilder->shouldReceive('setSpanKind')->with(SpanKind::KIND_INTERNAL)->once();

            $result = $this->spanBuilder->asInternal();

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets as producer', function () {
            $this->mockBuilder->shouldReceive('setSpanKind')->with(SpanKind::KIND_PRODUCER)->once();

            $result = $this->spanBuilder->asProducer();

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets as consumer', function () {
            $this->mockBuilder->shouldReceive('setSpanKind')->with(SpanKind::KIND_CONSUMER)->once();

            $result = $this->spanBuilder->asConsumer();

            expect($result)->toBe($this->spanBuilder);
        });
    });

    describe('Context Operations', function () {
        it('sets parent context', function () {
            $mockContext = Mockery::mock(ContextInterface::class);
            $this->mockBuilder->shouldReceive('setParent')->with($mockContext)->once();

            $result = $this->spanBuilder->setParent($mockContext);

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets no parent', function () {
            $this->mockBuilder->shouldReceive('setNoParent')->once();

            $result = $this->spanBuilder->setNoParent();

            expect($result)->toBe($this->spanBuilder);
        });

        it('does not set no parent when false', function () {
            $this->mockBuilder->shouldNotReceive('setNoParent');

            $result = $this->spanBuilder->setNoParent(false);

            expect($result)->toBe($this->spanBuilder);
        });

        it('adds link', function () {
            $mockSpanContext = Mockery::mock(SpanContextInterface::class);
            $this->mockBuilder->shouldReceive('addLink')->with($mockSpanContext, [])->once();

            $result = $this->spanBuilder->addLink($mockSpanContext);

            expect($result)->toBe($this->spanBuilder);
        });

        it('adds link with attributes', function () {
            $mockSpanContext = Mockery::mock(SpanContextInterface::class);
            $attributes = ['link_type' => 'follows_from'];
            $this->mockBuilder->shouldReceive('addLink')->with($mockSpanContext, $attributes)->once();

            $this->spanBuilder->addLink($mockSpanContext, $attributes);
        });
    });

    describe('Attributes', function () {
        it('sets single attribute', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->with('key', 'value')->once();

            $result = $this->spanBuilder->setAttribute('key', 'value');

            expect($result)->toBe($this->spanBuilder);
        });

        it('skips null attribute values', function () {
            $this->mockBuilder->shouldNotReceive('setAttribute');

            $result = $this->spanBuilder->setAttribute('key', null);

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets multiple attributes', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->twice();

            $result = $this->spanBuilder->setAttributes([
                'key1' => 'value1',
                'key2' => 'value2',
            ]);

            expect($result)->toBe($this->spanBuilder);
        });

        it('tracks set attributes', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->twice();

            $this->spanBuilder->setAttributes([
                'key1' => 'value1',
                'key2' => 'value2',
            ]);

            expect($this->spanBuilder->getAttributes())->toBe([
                'key1' => 'value1',
                'key2' => 'value2',
            ]);
        });
    });

    describe('Timestamp', function () {
        it('sets start timestamp', function () {
            $timestamp = hrtime(true);
            $this->mockBuilder->shouldReceive('setStartTimestamp')->with($timestamp)->once();

            $result = $this->spanBuilder->setStartTimestamp($timestamp);

            expect($result)->toBe($this->spanBuilder);
        });
    });

    describe('GenAI Helpers', function () {
        it('sets GenAI operation', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->times(3);

            $result = $this->spanBuilder->forGenAiOperation('chat', 'openai', 'gpt-4');

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets GenAI request params', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->times(3);

            $result = $this->spanBuilder->withGenAiRequestParams([
                'temperature' => 0.7,
                'max_tokens' => 100,
                'top_p' => 0.9,
            ]);

            expect($result)->toBe($this->spanBuilder);
        });

        it('sets server attributes', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->times(2);

            $result = $this->spanBuilder->withServerAttributes('api.openai.com');

            expect($result)->toBe($this->spanBuilder);
        });

        it('configures for chat', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->times(3);

            $result = $this->spanBuilder->forChat('anthropic', 'claude-3-opus');

            expect($result)->toBe($this->spanBuilder);
        });

        it('configures for embeddings', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->times(3);

            $result = $this->spanBuilder->forEmbeddings('openai', 'text-embedding-3-small');

            expect($result)->toBe($this->spanBuilder);
        });

        it('configures for tool execution', function () {
            $this->mockBuilder->shouldReceive('setAttribute')->times(2);

            $result = $this->spanBuilder->forToolExecution('search_web');

            expect($result)->toBe($this->spanBuilder);
        });
    });

    describe('Start Operations', function () {
        it('starts span', function () {
            $mockSpan = Mockery::mock(SpanInterface::class);
            $mockSpan->shouldReceive('isRecording')->andReturn(false);
            $this->mockBuilder->shouldReceive('startSpan')->once()->andReturn($mockSpan);

            $span = $this->spanBuilder->start();

            expect($span)->toBeInstanceOf(Span::class);
        });

        it('starts and activates span', function () {
            $mockSpan = Mockery::mock(SpanInterface::class);
            $mockScope = Mockery::mock(\OpenTelemetry\Context\ScopeInterface::class);
            $mockScope->shouldReceive('detach');
            $mockSpan->shouldReceive('isRecording')->andReturn(false);
            $mockSpan->shouldReceive('activate')->andReturn($mockScope);
            $this->mockBuilder->shouldReceive('startSpan')->once()->andReturn($mockSpan);

            $result = $this->spanBuilder->startAndActivate();

            expect($result)->toHaveKey('span');
            expect($result)->toHaveKey('scope');
            expect($result['span'])->toBeInstanceOf(Span::class);
        });

        it('returns underlying builder', function () {
            expect($this->spanBuilder->getBuilder())->toBe($this->mockBuilder);
        });
    });
});
