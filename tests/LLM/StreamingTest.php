<?php

use Illuminate\Support\Facades\Event;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\Drivers\BaseDriver;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\OpenAI;
use Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse;
use Mindwave\Mindwave\Observability\Events\LlmTokenStreamed;
use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiInstrumentor;
use Mindwave\Mindwave\Observability\Tracing\GenAI\LLMDriverInstrumentorDecorator;
use Mindwave\Mindwave\Observability\Tracing\TracerManager;
use OpenAI\Client;
use OpenAI\Contracts\ClientContract;

beforeEach(function () {
    // Create a mock OpenAI client
    $this->mockClient = Mockery::mock(ClientContract::class);

    // Create a test driver
    $this->driver = new OpenAI($this->mockClient, model: 'gpt-4');
});

afterEach(function () {
    Mockery::close();
});

it('extracts content from chat completion stream chunks', function () {
    // Test the content extraction logic directly
    $driver = new OpenAI($this->mockClient, model: 'gpt-4');

    // Use reflection to access protected method
    $reflection = new ReflectionClass($driver);
    $method = $reflection->getMethod('extractStreamedContent');
    $method->setAccessible(true);

    // Test chat completion format
    $chatChunk = (object) ['choices' => [(object) ['delta' => (object) ['content' => 'Hello world']]]];
    expect($method->invoke($driver, $chatChunk))->toBe('Hello world');

    // Test empty content
    $emptyChunk = (object) ['choices' => [(object) ['delta' => (object) ['content' => '']]]];
    expect($method->invoke($driver, $emptyChunk))->toBe('');

    // Test missing content field
    $noContentChunk = (object) ['choices' => [(object) ['delta' => (object) []]]];
    expect($method->invoke($driver, $noContentChunk))->toBe('');

    // Test legacy completion format
    $completionChunk = (object) ['choices' => [(object) ['text' => 'Legacy text']]];
    expect($method->invoke($driver, $completionChunk))->toBe('Legacy text');
});

it('throws exception for drivers that do not support streaming', function () {
    // Create a mock driver that extends BaseDriver but doesn't override streamText
    $driver = new class extends BaseDriver
    {
        public function generateText(string $prompt): ?string
        {
            return 'test';
        }
    };

    expect(fn () => $driver->streamText('test'))
        ->toThrow(BadMethodCallException::class, 'Streaming is not supported');
});

it('can create StreamedTextResponse from generator', function () {
    $generator = (function () {
        yield 'Hello ';
        yield 'world!';
    })();

    $response = new StreamedTextResponse($generator);

    expect($response)->toBeInstanceOf(StreamedTextResponse::class);
});

it('can convert stream to string', function () {
    $generator = (function () {
        yield 'Hello ';
        yield 'world!';
    })();

    $response = new StreamedTextResponse($generator);
    $result = $response->toString();

    expect($result)->toBe('Hello world!');
});

it('can get raw iterator from StreamedTextResponse', function () {
    $generator = (function () {
        yield 'test';
    })();

    $response = new StreamedTextResponse($generator);
    $iterator = $response->getIterator();

    expect($iterator)->toBeInstanceOf(Generator::class);
});

it('can process chunks with onChunk callback', function () {
    $generator = (function () {
        yield 'Hello ';
        yield 'world!';
    })();

    $collected = [];
    $response = new StreamedTextResponse($generator);
    $response->onChunk(function ($chunk) use (&$collected) {
        $collected[] = $chunk;
    });

    // Consume the stream
    $result = $response->toString();

    expect($collected)->toBe(['Hello ', 'world!']);
    expect($result)->toBe('Hello world!');
});

it('creates SSE formatted streamed response', function () {
    $generator = (function () {
        yield 'test';
    })();

    $response = new StreamedTextResponse($generator);
    $httpResponse = $response->toStreamedResponse();

    expect($httpResponse)->toBeInstanceOf(Symfony\Component\HttpFoundation\StreamedResponse::class);
    expect($httpResponse->headers->get('Content-Type'))->toBe('text/event-stream');
    expect($httpResponse->headers->get('Cache-Control'))->toContain('no-cache');
});

it('creates plain text streamed response', function () {
    $generator = (function () {
        yield 'test';
    })();

    $response = new StreamedTextResponse($generator);
    $httpResponse = $response->toPlainStreamedResponse();

    expect($httpResponse)->toBeInstanceOf(Symfony\Component\HttpFoundation\StreamedResponse::class);
    expect($httpResponse->headers->get('Content-Type'))->toContain('text/plain');
});

it('fires LlmTokenStreamed events during streaming', function () {
    Event::fake([LlmTokenStreamed::class]);

    $tracerManager = Mockery::mock(TracerManager::class);
    $instrumentor = new GenAiInstrumentor($tracerManager, captureMessages: false, enabled: true);

    // Mock the tracer manager to return a mock span
    $mockScope = Mockery::mock(\OpenTelemetry\Context\ScopeInterface::class);
    $mockScope->shouldReceive('detach');

    $mockSpan = Mockery::mock(\Mindwave\Mindwave\Observability\Tracing\Span::class);
    $mockSpan->shouldReceive('setGenAiRequestParams')->andReturnSelf();
    $mockSpan->shouldReceive('setServerAttributes')->andReturnSelf();
    $mockSpan->shouldReceive('activate')->andReturn($mockScope);
    $mockSpan->shouldReceive('end')->andReturnSelf();
    $mockSpan->shouldReceive('markAsOk')->andReturnSelf();
    $mockSpan->shouldReceive('setGenAiUsage')->andReturnSelf();
    $mockSpan->shouldReceive('getSpanId')->andReturn('test-span-id');
    $mockSpan->shouldReceive('getTraceId')->andReturn('test-trace-id');

    $tracerManager->shouldReceive('startSpan')->andReturn($mockSpan);

    // Create the execute callback that returns a generator
    $execute = function () {
        yield 'Hello';
        yield 'World';
    };

    // Instrument the streaming call
    $stream = $instrumentor->instrumentStreamedChatCompletion(
        provider: 'openai',
        model: 'gpt-4',
        prompt: 'Test prompt',
        options: [],
        execute: $execute
    );

    // Consume the stream
    $chunks = iterator_to_array($stream);

    // Verify events were fired
    Event::assertDispatched(LlmTokenStreamed::class, 2); // Should fire for each chunk

    expect($chunks)->toBe(['Hello', 'World']);
});

it('decorator supports streamText when driver implements it', function () {
    // Create a mock driver that supports streaming
    $driver = Mockery::mock(LLM::class);
    $driver->shouldReceive('streamText')
        ->once()
        ->with('test prompt')
        ->andReturn((function () {
            yield 'test';
        })());

    $tracerManager = Mockery::mock(TracerManager::class);
    $instrumentor = new GenAiInstrumentor($tracerManager, false, false); // disabled for simplicity

    $decorator = new LLMDriverInstrumentorDecorator($driver, $instrumentor, 'test-provider');

    $stream = $decorator->streamText('test prompt');
    $chunks = iterator_to_array($stream);

    expect($chunks)->toBe(['test']);
});

it('decorator throws exception when driver does not support streamText', function () {
    // Create a real driver that extends BaseDriver but doesn't override streamText
    $driver = new class extends BaseDriver
    {
        public function generateText(string $prompt): ?string
        {
            return 'test';
        }
    };

    $tracerManager = Mockery::mock(TracerManager::class);
    $instrumentor = new GenAiInstrumentor($tracerManager, false, false);

    $decorator = new LLMDriverInstrumentorDecorator($driver, $instrumentor, 'test-provider');

    // The decorator should pass through to the driver's streamText, which throws an exception
    expect(fn () => iterator_to_array($decorator->streamText('test')))
        ->toThrow(BadMethodCallException::class);
});

it('instrumentation tracks cumulative tokens during streaming', function () {
    $tracerManager = Mockery::mock(TracerManager::class);
    $instrumentor = new GenAiInstrumentor($tracerManager, captureMessages: false, enabled: true);

    // Mock the tracer manager to return a mock span
    $mockScope = Mockery::mock(\OpenTelemetry\Context\ScopeInterface::class);
    $mockScope->shouldReceive('detach');

    $mockSpan = Mockery::mock(\Mindwave\Mindwave\Observability\Tracing\Span::class);
    $mockSpan->shouldReceive('setGenAiRequestParams')->andReturnSelf();
    $mockSpan->shouldReceive('setServerAttributes')->andReturnSelf();
    $mockSpan->shouldReceive('activate')->andReturn($mockScope);
    $mockSpan->shouldReceive('end')->andReturnSelf();
    $mockSpan->shouldReceive('markAsOk')->andReturnSelf();
    $mockSpan->shouldReceive('getSpanId')->andReturn('test-span-id');
    $mockSpan->shouldReceive('getTraceId')->andReturn('test-trace-id');

    // This is the key assertion - verify cumulative tokens are tracked
    $mockSpan->shouldReceive('setGenAiUsage')
        ->once()
        ->with(
            Mockery::on(fn ($inputTokens) => $inputTokens === null),  // Input tokens not available in streaming
            Mockery::on(fn ($outputTokens) => $outputTokens === 3)    // Should count 3 chunks
        )
        ->andReturnSelf();

    $tracerManager->shouldReceive('startSpan')->andReturn($mockSpan);

    // Create the execute callback that returns a generator with 3 chunks
    $execute = function () {
        yield 'First';
        yield 'Second';
        yield 'Third';
    };

    // Instrument the streaming call
    $stream = $instrumentor->instrumentStreamedChatCompletion(
        provider: 'openai',
        model: 'gpt-4',
        prompt: 'Test prompt',
        options: [],
        execute: $execute
    );

    // Consume the stream - this is when tracking happens
    $chunks = iterator_to_array($stream);

    // Verify chunks were yielded correctly
    expect($chunks)->toBe(['First', 'Second', 'Third']);
});
