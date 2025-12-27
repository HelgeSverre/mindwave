<?php

declare(strict_types=1);

use Mindwave\Mindwave\LLM\Responses\ChatResponse;

describe('ChatResponse', function () {
    describe('Construction', function () {
        it('creates response with required content', function () {
            $response = new ChatResponse(content: 'Hello, world!');

            expect($response->content)->toBe('Hello, world!');
        });

        it('creates response with all parameters', function () {
            $response = new ChatResponse(
                content: 'Response text',
                role: 'assistant',
                inputTokens: 100,
                outputTokens: 50,
                finishReason: 'stop',
                model: 'gpt-4',
                raw: ['key' => 'value'],
            );

            expect($response->content)->toBe('Response text');
            expect($response->role)->toBe('assistant');
            expect($response->inputTokens)->toBe(100);
            expect($response->outputTokens)->toBe(50);
            expect($response->finishReason)->toBe('stop');
            expect($response->model)->toBe('gpt-4');
            expect($response->raw)->toBe(['key' => 'value']);
        });

        it('has null defaults for optional parameters', function () {
            $response = new ChatResponse(content: 'Content');

            expect($response->role)->toBeNull();
            expect($response->inputTokens)->toBeNull();
            expect($response->outputTokens)->toBeNull();
            expect($response->finishReason)->toBeNull();
            expect($response->model)->toBeNull();
            expect($response->raw)->toBe([]);
        });
    });

    describe('Readonly Properties', function () {
        it('is a readonly class', function () {
            $reflection = new ReflectionClass(ChatResponse::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });
    });

    describe('Content', function () {
        it('handles empty content', function () {
            $response = new ChatResponse(content: '');

            expect($response->content)->toBe('');
        });

        it('handles unicode content', function () {
            $response = new ChatResponse(content: '日本語テキスト 🎉');

            expect($response->content)->toBe('日本語テキスト 🎉');
        });

        it('handles multiline content', function () {
            $content = "Line 1\nLine 2\nLine 3";
            $response = new ChatResponse(content: $content);

            expect($response->content)->toBe($content);
        });
    });

    describe('Roles', function () {
        it('handles user role', function () {
            $response = new ChatResponse(content: 'test', role: 'user');

            expect($response->role)->toBe('user');
        });

        it('handles assistant role', function () {
            $response = new ChatResponse(content: 'test', role: 'assistant');

            expect($response->role)->toBe('assistant');
        });

        it('handles system role', function () {
            $response = new ChatResponse(content: 'test', role: 'system');

            expect($response->role)->toBe('system');
        });
    });

    describe('Token Counts', function () {
        it('handles zero token counts', function () {
            $response = new ChatResponse(
                content: 'test',
                inputTokens: 0,
                outputTokens: 0,
            );

            expect($response->inputTokens)->toBe(0);
            expect($response->outputTokens)->toBe(0);
        });

        it('handles large token counts', function () {
            $response = new ChatResponse(
                content: 'test',
                inputTokens: 128000,
                outputTokens: 4096,
            );

            expect($response->inputTokens)->toBe(128000);
            expect($response->outputTokens)->toBe(4096);
        });
    });

    describe('Finish Reasons', function () {
        it('handles stop finish reason', function () {
            $response = new ChatResponse(content: 'test', finishReason: 'stop');

            expect($response->finishReason)->toBe('stop');
        });

        it('handles length finish reason', function () {
            $response = new ChatResponse(content: 'test', finishReason: 'length');

            expect($response->finishReason)->toBe('length');
        });

        it('handles tool_calls finish reason', function () {
            $response = new ChatResponse(content: 'test', finishReason: 'tool_calls');

            expect($response->finishReason)->toBe('tool_calls');
        });
    });

    describe('Raw Response', function () {
        it('stores complete raw response', function () {
            $raw = [
                'id' => 'chatcmpl-123',
                'object' => 'chat.completion',
                'created' => 1677652288,
                'choices' => [
                    ['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'test']],
                ],
            ];

            $response = new ChatResponse(content: 'test', raw: $raw);

            expect($response->raw)->toBe($raw);
            expect($response->raw['id'])->toBe('chatcmpl-123');
        });
    });
});
