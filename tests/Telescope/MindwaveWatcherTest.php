<?php

use Illuminate\Support\Facades\Event;
use Mindwave\Mindwave\Observability\Events\LlmErrorOccurred;
use Mindwave\Mindwave\Observability\Events\LlmRequestStarted;
use Mindwave\Mindwave\Observability\Events\LlmResponseCompleted;
use Mindwave\Mindwave\Telescope\MindwaveWatcher;

describe('MindwaveWatcher', function () {
    describe('Event Registration', function () {
        it('registers event listeners on application events', function () {
            $watcher = new MindwaveWatcher([]);
            $watcher->register(app());

            $listeners = Event::getListeners(LlmResponseCompleted::class);
            expect($listeners)->not->toBeEmpty();

            $listeners = Event::getListeners(LlmRequestStarted::class);
            expect($listeners)->not->toBeEmpty();

            $listeners = Event::getListeners(LlmErrorOccurred::class);
            expect($listeners)->not->toBeEmpty();
        });
    });

    describe('Request Recording', function () {
        it('stores pending request data for correlation', function () {
            $watcher = new MindwaveWatcher(['capture_messages' => true]);
            $watcher->register(app());

            $event = new LlmRequestStarted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                messages: [['role' => 'user', 'content' => 'Hello']],
                parameters: ['temperature' => 0.7],
                spanId: 'test-span-123',
                traceId: 'test-trace-456',
                timestamp: hrtime(true)
            );

            $watcher->recordRequest($event);

            $reflection = new ReflectionClass($watcher);
            $property = $reflection->getProperty('pendingRequests');
            $property->setAccessible(true);
            $pending = $property->getValue($watcher);

            expect($pending)->toHaveKey('test-span-123');
            expect($pending['test-span-123']['messages'])->toBe([['role' => 'user', 'content' => 'Hello']]);
        });

        it('does not store messages when capture disabled', function () {
            $watcher = new MindwaveWatcher(['capture_messages' => false]);
            $watcher->register(app());

            $event = new LlmRequestStarted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                messages: [['role' => 'user', 'content' => 'Secret message']],
                parameters: [],
                spanId: 'test-span-private',
                traceId: 'test-trace-456',
                timestamp: hrtime(true)
            );

            $watcher->recordRequest($event);

            $reflection = new ReflectionClass($watcher);
            $property = $reflection->getProperty('pendingRequests');
            $property->setAccessible(true);
            $pending = $property->getValue($watcher);

            expect($pending['test-span-private']['messages'])->toBeNull();
        });
    });

    describe('Response Recording', function () {
        it('cleans up pending request after recording response', function () {
            $watcher = new MindwaveWatcher([]);
            $watcher->register(app());

            // First, record a request
            $requestEvent = new LlmRequestStarted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                messages: [],
                parameters: [],
                spanId: 'test-span-cleanup',
                traceId: 'test-trace-456',
                timestamp: hrtime(true)
            );
            $watcher->recordRequest($requestEvent);

            // Then record response
            $responseEvent = new LlmResponseCompleted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                response: ['id' => 'resp-123', 'finish_reason' => 'stop'],
                tokenUsage: ['input_tokens' => 10, 'output_tokens' => 5],
                duration: 1_000_000_000,
                costEstimate: 0.001,
                spanId: 'test-span-cleanup',
                traceId: 'test-trace-456',
                timestamp: hrtime(true)
            );
            $watcher->recordResponse($responseEvent);

            $reflection = new ReflectionClass($watcher);
            $property = $reflection->getProperty('pendingRequests');
            $property->setAccessible(true);
            $pending = $property->getValue($watcher);

            expect($pending)->not->toHaveKey('test-span-cleanup');
        });
    });

    describe('URI Formatting', function () {
        it('formats URI as provider://operation/model', function () {
            $watcher = new MindwaveWatcher([]);

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('formatUri');
            $method->setAccessible(true);

            $uri = $method->invoke($watcher, 'openai', 'chat', 'gpt-4-turbo');
            expect($uri)->toBe('openai://chat/gpt-4-turbo');

            $uri = $method->invoke($watcher, 'anthropic', 'text_completion', 'claude-3-opus');
            expect($uri)->toBe('anthropic://text_completion/claude-3-opus');
        });
    });

    describe('Tag Building', function () {
        it('includes base tags for all responses', function () {
            $watcher = new MindwaveWatcher([]);

            $event = new LlmResponseCompleted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                response: [],
                tokenUsage: ['input_tokens' => 10, 'output_tokens' => 5],
                duration: 1_000_000_000,
                costEstimate: 0.001,
                spanId: 'span-1',
                traceId: 'trace-1',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildTags');
            $method->setAccessible(true);
            $tags = $method->invoke($watcher, $event);

            expect($tags)->toContain('mindwave');
            expect($tags)->toContain('provider:openai');
            expect($tags)->toContain('model:gpt-4');
            expect($tags)->toContain('operation:chat');
        });

        it('adds slow tag when duration exceeds threshold', function () {
            $watcher = new MindwaveWatcher(['slow' => 1000]); // 1 second threshold

            $event = new LlmResponseCompleted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                response: [],
                tokenUsage: [],
                duration: 2_000_000_000, // 2 seconds
                costEstimate: null,
                spanId: 'span-slow',
                traceId: 'trace-1',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildTags');
            $method->setAccessible(true);
            $tags = $method->invoke($watcher, $event);

            expect($tags)->toContain('slow');
        });

        it('does not add slow tag when under threshold', function () {
            $watcher = new MindwaveWatcher(['slow' => 5000]); // 5 second threshold

            $event = new LlmResponseCompleted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                response: [],
                tokenUsage: [],
                duration: 1_000_000_000, // 1 second
                costEstimate: null,
                spanId: 'span-fast',
                traceId: 'trace-1',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildTags');
            $method->setAccessible(true);
            $tags = $method->invoke($watcher, $event);

            expect($tags)->not->toContain('slow');
        });

        it('adds expensive tag when cost exceeds threshold', function () {
            $watcher = new MindwaveWatcher(['cost_threshold' => 0.05]); // $0.05 threshold

            $event = new LlmResponseCompleted(
                provider: 'anthropic',
                model: 'claude-3-opus',
                operation: 'chat',
                response: [],
                tokenUsage: [],
                duration: 1_000_000_000,
                costEstimate: 0.15, // $0.15
                spanId: 'span-expensive',
                traceId: 'trace-1',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildTags');
            $method->setAccessible(true);
            $tags = $method->invoke($watcher, $event);

            expect($tags)->toContain('expensive');
        });

        it('adds cached tag when cache was used', function () {
            $watcher = new MindwaveWatcher([]);

            $event = new LlmResponseCompleted(
                provider: 'anthropic',
                model: 'claude-3-opus',
                operation: 'chat',
                response: [],
                tokenUsage: ['cache_read_tokens' => 500],
                duration: 1_000_000_000,
                costEstimate: 0.01,
                spanId: 'span-cached',
                traceId: 'trace-1',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildTags');
            $method->setAccessible(true);
            $tags = $method->invoke($watcher, $event);

            expect($tags)->toContain('cached');
        });
    });

    describe('Error Tag Building', function () {
        it('includes error-specific tags', function () {
            $watcher = new MindwaveWatcher([]);

            $event = new LlmErrorOccurred(
                exception: new RuntimeException('API rate limit exceeded', 429),
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                spanId: 'span-error',
                traceId: 'trace-1',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildErrorTags');
            $method->setAccessible(true);
            $tags = $method->invoke($watcher, $event);

            expect($tags)->toContain('mindwave');
            expect($tags)->toContain('mindwave-error');
            expect($tags)->toContain('provider:openai');
        });
    });

    describe('Mindwave Data Building', function () {
        it('builds comprehensive mindwave data from response', function () {
            $watcher = new MindwaveWatcher([]);

            $event = new LlmResponseCompleted(
                provider: 'openai',
                model: 'gpt-4-turbo',
                operation: 'chat',
                response: ['id' => 'chatcmpl-123', 'finish_reason' => 'stop'],
                tokenUsage: [
                    'input_tokens' => 150,
                    'output_tokens' => 89,
                    'cache_read_tokens' => 50,
                    'cache_creation_tokens' => 0,
                ],
                duration: 1_500_000_000, // 1.5 seconds
                costEstimate: 0.0045,
                spanId: 'span-data',
                traceId: 'trace-data',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildMindwaveData');
            $method->setAccessible(true);
            $data = $method->invoke($watcher, $event);

            expect($data['provider'])->toBe('openai');
            expect($data['model'])->toBe('gpt-4-turbo');
            expect($data['operation'])->toBe('chat');
            expect($data['input_tokens'])->toBe(150);
            expect($data['output_tokens'])->toBe(89);
            expect($data['total_tokens'])->toBe(239);
            expect($data['cache_read_tokens'])->toBe(50);
            expect($data['used_cache'])->toBeTrue();
            expect($data['cost'])->toBe('$0.0045');
            expect($data['cost_raw'])->toBe(0.0045);
            expect($data['finish_reason'])->toBe('stop');
            expect($data['response_id'])->toBe('chatcmpl-123');
            expect($data['span_id'])->toBe('span-data');
            expect($data['trace_id'])->toBe('trace-data');
        });
    });

    describe('Configuration', function () {
        it('uses default slow threshold of 5000ms', function () {
            $watcher = new MindwaveWatcher([]);

            $event = new LlmResponseCompleted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                response: [],
                tokenUsage: [],
                duration: 4_999_000_000, // Just under 5 seconds
                costEstimate: null,
                spanId: 'span-1',
                traceId: 'trace-1',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildTags');
            $method->setAccessible(true);
            $tags = $method->invoke($watcher, $event);

            expect($tags)->not->toContain('slow');
        });

        it('uses default cost threshold of $0.10', function () {
            $watcher = new MindwaveWatcher([]);

            $event = new LlmResponseCompleted(
                provider: 'openai',
                model: 'gpt-4',
                operation: 'chat',
                response: [],
                tokenUsage: [],
                duration: 1_000_000_000,
                costEstimate: 0.09, // Just under $0.10
                spanId: 'span-1',
                traceId: 'trace-1',
                timestamp: hrtime(true)
            );

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('buildTags');
            $method->setAccessible(true);
            $tags = $method->invoke($watcher, $event);

            expect($tags)->not->toContain('expensive');
        });

        it('defaults capture_messages to true', function () {
            $watcher = new MindwaveWatcher([]);

            $reflection = new ReflectionClass($watcher);
            $method = $reflection->getMethod('shouldCaptureMessages');
            $method->setAccessible(true);

            expect($method->invoke($watcher))->toBeTrue();
        });
    });
});
