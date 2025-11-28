<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Events\LlmErrorOccurred;
use Mindwave\Mindwave\Observability\Events\LlmRequestStarted;
use Mindwave\Mindwave\Observability\Events\LlmResponseCompleted;

describe('LlmRequestStarted', function () {
    it('creates an event with all properties', function () {
        $event = new LlmRequestStarted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            messages: [['role' => 'user', 'content' => 'Hello']],
            parameters: ['temperature' => 0.7, 'max_tokens' => 100],
            spanId: 'span-123',
            traceId: 'trace-456',
            timestamp: 1_000_000_000_000
        );

        expect($event->provider)->toBe('openai');
        expect($event->model)->toBe('gpt-4');
        expect($event->operation)->toBe('chat');
        expect($event->messages)->toBe([['role' => 'user', 'content' => 'Hello']]);
        expect($event->parameters)->toBe(['temperature' => 0.7, 'max_tokens' => 100]);
        expect($event->spanId)->toBe('span-123');
        expect($event->traceId)->toBe('trace-456');
        expect($event->timestamp)->toBe(1_000_000_000_000);
    });

    it('returns parameters via getParameters', function () {
        $event = new LlmRequestStarted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            messages: [],
            parameters: ['temperature' => 0.7],
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getParameters())->toBe(['temperature' => 0.7]);
    });

    it('returns specific parameter via getParameter', function () {
        $event = new LlmRequestStarted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            messages: [],
            parameters: ['temperature' => 0.7, 'max_tokens' => 100],
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getParameter('temperature'))->toBe(0.7);
        expect($event->getParameter('max_tokens'))->toBe(100);
        expect($event->getParameter('missing'))->toBeNull();
        expect($event->getParameter('missing', 'default'))->toBe('default');
    });

    it('checks if messages are captured', function () {
        $withMessages = new LlmRequestStarted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            messages: [['role' => 'user', 'content' => 'Hello']],
            parameters: [],
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        $withoutMessages = new LlmRequestStarted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            messages: null,
            parameters: [],
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($withMessages->hasMessages())->toBeTrue();
        expect($withoutMessages->hasMessages())->toBeFalse();
    });

    it('converts timestamp to seconds', function () {
        $event = new LlmRequestStarted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            messages: [],
            parameters: [],
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: 1_500_000_000 // 1.5 seconds in nanoseconds
        );

        expect($event->getTimestampInSeconds())->toBe(1.5);
    });

    it('converts timestamp to milliseconds', function () {
        $event = new LlmRequestStarted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            messages: [],
            parameters: [],
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: 1_500_000_000 // 1.5 seconds
        );

        expect($event->getTimestampInMilliseconds())->toBe(1500.0);
    });

    it('converts to array', function () {
        $event = new LlmRequestStarted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            messages: [['role' => 'user', 'content' => 'Hello']],
            parameters: ['temperature' => 0.7],
            spanId: 'span-123',
            traceId: 'trace-456',
            timestamp: 1_000_000_000
        );

        $array = $event->toArray();

        expect($array)->toHaveKey('provider');
        expect($array)->toHaveKey('model');
        expect($array)->toHaveKey('operation');
        expect($array)->toHaveKey('messages');
        expect($array)->toHaveKey('parameters');
        expect($array)->toHaveKey('span_id');
        expect($array)->toHaveKey('trace_id');
        expect($array)->toHaveKey('timestamp');
    });
});

describe('LlmResponseCompleted', function () {
    it('creates an event with all properties', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: ['id' => 'chatcmpl-123', 'finish_reason' => 'stop'],
            tokenUsage: ['input_tokens' => 100, 'output_tokens' => 50],
            duration: 1_500_000_000,
            costEstimate: 0.0045,
            spanId: 'span-123',
            traceId: 'trace-456',
            timestamp: 1_000_000_000_000
        );

        expect($event->provider)->toBe('openai');
        expect($event->model)->toBe('gpt-4');
        expect($event->operation)->toBe('chat');
        expect($event->response)->toBe(['id' => 'chatcmpl-123', 'finish_reason' => 'stop']);
        expect($event->costEstimate)->toBe(0.0045);
    });

    it('returns response id', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: ['id' => 'chatcmpl-123'],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getResponseId())->toBe('chatcmpl-123');
    });

    it('returns null for missing response id', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getResponseId())->toBeNull();
    });

    it('returns finish reason', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: ['finish_reason' => 'stop'],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getFinishReason())->toBe('stop');
    });

    it('returns token counts', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [
                'input_tokens' => 100,
                'output_tokens' => 50,
                'cache_read_tokens' => 25,
                'cache_creation_tokens' => 10,
            ],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getInputTokens())->toBe(100);
        expect($event->getOutputTokens())->toBe(50);
        expect($event->getTotalTokens())->toBe(150);
        expect($event->getCacheReadTokens())->toBe(25);
        expect($event->getCacheCreationTokens())->toBe(10);
    });

    it('returns 0 for missing token counts', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getInputTokens())->toBe(0);
        expect($event->getOutputTokens())->toBe(0);
        expect($event->getTotalTokens())->toBe(0);
        expect($event->getCacheReadTokens())->toBe(0);
        expect($event->getCacheCreationTokens())->toBe(0);
    });

    it('detects cache usage', function () {
        $withCacheRead = new LlmResponseCompleted(
            provider: 'anthropic',
            model: 'claude-3-opus',
            operation: 'chat',
            response: [],
            tokenUsage: ['cache_read_tokens' => 100],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        $withCacheCreation = new LlmResponseCompleted(
            provider: 'anthropic',
            model: 'claude-3-opus',
            operation: 'chat',
            response: [],
            tokenUsage: ['cache_creation_tokens' => 50],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        $noCache = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: ['input_tokens' => 100],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($withCacheRead->usedCache())->toBeTrue();
        expect($withCacheCreation->usedCache())->toBeTrue();
        expect($noCache->usedCache())->toBeFalse();
    });

    it('converts duration to seconds', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 2_500_000_000, // 2.5 seconds
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getDurationInSeconds())->toBe(2.5);
    });

    it('converts duration to milliseconds', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 1_500_000_000, // 1.5 seconds
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getDurationInMilliseconds())->toBe(1500.0);
    });

    it('calculates tokens per second', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: ['input_tokens' => 100, 'output_tokens' => 100],
            duration: 2_000_000_000, // 2 seconds
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getTokensPerSecond())->toBe(100.0);
    });

    it('returns 0 tokens per second when duration is 0', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: ['input_tokens' => 100, 'output_tokens' => 100],
            duration: 0,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getTokensPerSecond())->toBe(0.0);
    });

    it('checks for cost estimate', function () {
        $withCost = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: 0.0045,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        $withoutCost = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($withCost->hasCostEstimate())->toBeTrue();
        expect($withoutCost->hasCostEstimate())->toBeFalse();
    });

    it('formats cost estimate', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: 0.00456789,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getFormattedCost())->toBe('$0.0046');
        expect($event->getFormattedCost(2))->toBe('$0.00');
        expect($event->getFormattedCost(6))->toBe('$0.004568');
    });

    it('returns null for formatted cost when no cost estimate', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getFormattedCost())->toBeNull();
    });

    it('retrieves metadata', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: [],
            tokenUsage: [],
            duration: 1_000_000_000,
            costEstimate: null,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true),
            metadata: ['custom_key' => 'custom_value']
        );

        expect($event->getMetadata('custom_key'))->toBe('custom_value');
        expect($event->getMetadata('missing'))->toBeNull();
        expect($event->getMetadata('missing', 'default'))->toBe('default');
    });

    it('converts to array', function () {
        $event = new LlmResponseCompleted(
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            response: ['id' => 'test'],
            tokenUsage: ['input_tokens' => 100],
            duration: 1_000_000_000,
            costEstimate: 0.01,
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: 1_000_000_000
        );

        $array = $event->toArray();

        expect($array)->toHaveKey('provider');
        expect($array)->toHaveKey('model');
        expect($array)->toHaveKey('operation');
        expect($array)->toHaveKey('response');
        expect($array)->toHaveKey('token_usage');
        expect($array)->toHaveKey('duration');
        expect($array)->toHaveKey('duration_ms');
        expect($array)->toHaveKey('cost_estimate');
        expect($array)->toHaveKey('span_id');
        expect($array)->toHaveKey('trace_id');
        expect($array)->toHaveKey('metadata');
        expect($array['duration_ms'])->toBe(1000.0);
    });
});

describe('LlmErrorOccurred', function () {
    it('creates an event with exception', function () {
        $exception = new RuntimeException('API error', 500);

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-123',
            traceId: 'trace-456',
            timestamp: hrtime(true)
        );

        expect($event->provider)->toBe('openai');
        expect($event->model)->toBe('gpt-4');
        expect($event->operation)->toBe('chat');
        expect($event->exception)->toBe($exception);
    });

    it('returns exception message', function () {
        $exception = new RuntimeException('Rate limit exceeded');

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getMessage())->toBe('Rate limit exceeded');
    });

    it('returns exception code', function () {
        $exception = new RuntimeException('Error', 429);

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getCode())->toBe(429);
    });

    it('returns exception class name', function () {
        $exception = new InvalidArgumentException('Bad input');

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getExceptionClass())->toBe(InvalidArgumentException::class);
    });

    it('returns exception file and line', function () {
        $exception = new RuntimeException('Error');

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getFile())->toBeString();
        expect($event->getLine())->toBeInt();
    });

    it('returns stack trace', function () {
        $exception = new RuntimeException('Error');

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->getTrace())->toBeString();
    });

    it('handles previous exception', function () {
        $previous = new InvalidArgumentException('Original error');
        $exception = new RuntimeException('Wrapper error', 0, $previous);

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->hasPrevious())->toBeTrue();
        expect($event->getPrevious())->toBe($previous);
    });

    it('handles no previous exception', function () {
        $exception = new RuntimeException('Error');

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        expect($event->hasPrevious())->toBeFalse();
        expect($event->getPrevious())->toBeNull();
    });

    it('retrieves context', function () {
        $exception = new RuntimeException('Error');

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true),
            context: ['request_id' => 'req-123', 'retry_count' => 3]
        );

        expect($event->getContext('request_id'))->toBe('req-123');
        expect($event->getContext('retry_count'))->toBe(3);
        expect($event->getContext('missing'))->toBeNull();
        expect($event->getContext('missing', 'default'))->toBe('default');
    });

    it('returns error info', function () {
        $exception = new RuntimeException('API failed', 500);

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: hrtime(true)
        );

        $errorInfo = $event->getErrorInfo();

        expect($errorInfo)->toHaveKey('class');
        expect($errorInfo)->toHaveKey('message');
        expect($errorInfo)->toHaveKey('code');
        expect($errorInfo)->toHaveKey('file');
        expect($errorInfo)->toHaveKey('line');
        expect($errorInfo['class'])->toBe(RuntimeException::class);
        expect($errorInfo['message'])->toBe('API failed');
        expect($errorInfo['code'])->toBe(500);
    });

    it('converts to array', function () {
        $exception = new RuntimeException('Error');

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-123',
            traceId: 'trace-456',
            timestamp: 1_000_000_000,
            context: ['key' => 'value']
        );

        $array = $event->toArray();

        expect($array)->toHaveKey('exception');
        expect($array)->toHaveKey('provider');
        expect($array)->toHaveKey('model');
        expect($array)->toHaveKey('operation');
        expect($array)->toHaveKey('span_id');
        expect($array)->toHaveKey('trace_id');
        expect($array)->toHaveKey('timestamp');
        expect($array)->toHaveKey('context');
        expect($array['exception'])->toBeArray();
    });

    it('converts timestamp to seconds and milliseconds', function () {
        $exception = new RuntimeException('Error');

        $event = new LlmErrorOccurred(
            exception: $exception,
            provider: 'openai',
            model: 'gpt-4',
            operation: 'chat',
            spanId: 'span-1',
            traceId: 'trace-1',
            timestamp: 2_500_000_000
        );

        expect($event->getTimestampInSeconds())->toBe(2.5);
        expect($event->getTimestampInMilliseconds())->toBe(2500.0);
    });
});
