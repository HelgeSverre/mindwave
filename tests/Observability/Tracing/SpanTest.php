<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributes;
use Mindwave\Mindwave\Observability\Tracing\Span;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;

beforeEach(function () {
    $this->mockSpan = Mockery::mock(SpanInterface::class)->makePartial();
    $this->mockSpan->shouldReceive('isRecording')->andReturn(false)->byDefault();
    $this->mockSpan->shouldReceive('end')->byDefault(); // For destructor
    $this->span = new Span($this->mockSpan);
});

afterEach(function () {
    Mockery::close();
});

describe('Span', function () {
    describe('Basic Operations', function () {
        it('returns the underlying OTel span', function () {
            expect($this->span->getOtelSpan())->toBe($this->mockSpan);
        });

        it('sets a single attribute', function () {
            $this->mockSpan->shouldReceive('setAttribute')->with('key', 'value')->once();

            $result = $this->span->setAttribute('key', 'value');

            expect($result)->toBe($this->span);
        });

        it('skips null attribute values', function () {
            $this->mockSpan->shouldNotReceive('setAttribute');

            $result = $this->span->setAttribute('key', null);

            expect($result)->toBe($this->span);
        });

        it('sets multiple attributes', function () {
            $this->mockSpan->shouldReceive('setAttribute')->twice();

            $result = $this->span->setAttributes([
                'key1' => 'value1',
                'key2' => 'value2',
            ]);

            expect($result)->toBe($this->span);
        });
    });

    describe('Status Operations', function () {
        it('sets status with code and description', function () {
            $this->mockSpan->shouldReceive('setStatus')->with(StatusCode::STATUS_OK, 'Success')->once();

            $result = $this->span->setStatus(StatusCode::STATUS_OK, 'Success');

            expect($result)->toBe($this->span);
        });

        it('marks as OK', function () {
            $this->mockSpan->shouldReceive('setStatus')->with(StatusCode::STATUS_OK, null)->once();

            $result = $this->span->markAsOk();

            expect($result)->toBe($this->span);
        });

        it('marks as error', function () {
            $this->mockSpan->shouldReceive('setStatus')->with(StatusCode::STATUS_ERROR, 'Error message')->once();

            $result = $this->span->markAsError('Error message');

            expect($result)->toBe($this->span);
        });
    });

    describe('Exception Handling', function () {
        it('records exception and sets error status', function () {
            $exception = new RuntimeException('Test error');

            $this->mockSpan->shouldReceive('recordException')->with($exception, [])->once();
            $this->mockSpan->shouldReceive('setStatus')->once();

            $result = $this->span->recordException($exception);

            expect($result)->toBe($this->span);
        });
    });

    describe('Events', function () {
        it('adds event', function () {
            $this->mockSpan->shouldReceive('addEvent')->with('event_name', [], null)->once();

            $result = $this->span->addEvent('event_name');

            expect($result)->toBe($this->span);
        });

        it('adds event with attributes and timestamp', function () {
            $timestamp = hrtime(true);
            $this->mockSpan->shouldReceive('addEvent')->with('event_name', ['key' => 'value'], $timestamp)->once();

            $this->span->addEvent('event_name', ['key' => 'value'], $timestamp);
        });
    });

    describe('Name Update', function () {
        it('updates span name', function () {
            $this->mockSpan->shouldReceive('updateName')->with('new_name')->once();

            $result = $this->span->updateName('new_name');

            expect($result)->toBe($this->span);
        });
    });

    describe('Context Operations', function () {
        it('activates span', function () {
            $mockScope = Mockery::mock(ScopeInterface::class);
            $this->mockSpan->shouldReceive('activate')->once()->andReturn($mockScope);

            $scope = $this->span->activate();

            expect($scope)->toBe($mockScope);
        });

        it('detaches scope', function () {
            $mockScope = Mockery::mock(ScopeInterface::class);
            $mockScope->shouldReceive('detach')->once();
            $this->mockSpan->shouldReceive('activate')->andReturn($mockScope);

            $this->span->activate();
            $result = $this->span->detach();

            expect($result)->toBe($this->span);
        });

        it('handles detach when no scope active', function () {
            $result = $this->span->detach();

            expect($result)->toBe($this->span);
        });

        it('returns span context', function () {
            $mockContext = Mockery::mock(SpanContextInterface::class);
            $this->mockSpan->shouldReceive('getContext')->once()->andReturn($mockContext);

            $context = $this->span->getContext();

            expect($context)->toBe($mockContext);
        });
    });

    describe('End Operations', function () {
        it('ends span', function () {
            $this->mockSpan->shouldReceive('end')->with(null)->once();

            $this->span->end();
        });

        it('ends span with timestamp', function () {
            $timestamp = hrtime(true);
            $this->mockSpan->shouldReceive('end')->with($timestamp)->once();

            $this->span->end($timestamp);
        });

        it('checks if recording', function () {
            $this->mockSpan->shouldReceive('isRecording')->andReturn(true);

            expect($this->span->isRecording())->toBeTrue();
        });
    });

    describe('Wrap Callback', function () {
        it('wraps callback with span activation', function () {
            $mockScope = Mockery::mock(ScopeInterface::class);
            $mockScope->shouldReceive('detach')->once();
            $this->mockSpan->shouldReceive('activate')->once()->andReturn($mockScope);

            $result = $this->span->wrap(fn () => 'result');

            expect($result)->toBe('result');
        });

        it('records exception from wrapped callback', function () {
            $mockScope = Mockery::mock(ScopeInterface::class);
            $mockScope->shouldReceive('detach')->once();
            $this->mockSpan->shouldReceive('activate')->andReturn($mockScope);
            $this->mockSpan->shouldReceive('recordException')->once();
            $this->mockSpan->shouldReceive('setStatus')->once();

            expect(fn () => $this->span->wrap(fn () => throw new RuntimeException('Error')))
                ->toThrow(RuntimeException::class, 'Error');
        });
    });

    describe('GenAI Helpers', function () {
        it('sets GenAI operation attributes', function () {
            $this->mockSpan->shouldReceive('setAttribute')->times(3);

            $result = $this->span->setGenAiOperation('chat', 'openai', 'gpt-4');

            expect($result)->toBe($this->span);
        });

        it('sets GenAI request parameters', function () {
            $this->mockSpan->shouldReceive('setAttribute')->times(2);

            $this->span->setGenAiRequestParams([
                'temperature' => 0.7,
                'max_tokens' => 100,
            ]);
        });

        it('sets GenAI response attributes', function () {
            $this->mockSpan->shouldReceive('setAttribute')->times(2);

            $this->span->setGenAiResponse([
                'id' => 'resp-123',
                'model' => 'gpt-4-0613',
            ]);
        });

        it('sets GenAI usage attributes', function () {
            $this->mockSpan->shouldReceive('setAttribute')->times(3);

            $this->span->setGenAiUsage(100, 50);
        });

        it('sets GenAI cache usage attributes', function () {
            $this->mockSpan->shouldReceive('setAttribute')->times(5);

            $this->span->setGenAiUsage(100, 50, 25, 10);
        });

        it('sets GenAI input messages', function () {
            $messages = [['role' => 'user', 'content' => 'Hello']];
            $this->mockSpan->shouldReceive('setAttribute')
                ->with(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages)->once();

            $this->span->setGenAiInputMessages($messages);
        });

        it('sets GenAI output messages', function () {
            $messages = [['role' => 'assistant', 'content' => 'Hi!']];
            $this->mockSpan->shouldReceive('setAttribute')
                ->with(GenAiAttributes::GEN_AI_OUTPUT_MESSAGES, $messages)->once();

            $this->span->setGenAiOutputMessages($messages);
        });

        it('sets server attributes', function () {
            $this->mockSpan->shouldReceive('setAttribute')->times(2);

            $this->span->setServerAttributes('api.openai.com');
        });

        it('sets server attributes with custom port', function () {
            $this->mockSpan->shouldReceive('setAttribute')->times(2);

            $this->span->setServerAttributes('localhost', 11434);
        });
    });
});
