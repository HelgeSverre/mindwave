<?php

declare(strict_types=1);

use Mindwave\Mindwave\LLM\Responses\StreamChunk;

describe('StreamChunk', function () {
    describe('Construction', function () {
        it('creates chunk with no parameters', function () {
            $chunk = new StreamChunk;

            expect($chunk->content)->toBeNull();
            expect($chunk->role)->toBeNull();
            expect($chunk->finishReason)->toBeNull();
            expect($chunk->model)->toBeNull();
            expect($chunk->inputTokens)->toBeNull();
            expect($chunk->outputTokens)->toBeNull();
            expect($chunk->toolCalls)->toBeNull();
            expect($chunk->raw)->toBe([]);
        });

        it('creates chunk with content only', function () {
            $chunk = new StreamChunk(content: 'Hello');

            expect($chunk->content)->toBe('Hello');
            expect($chunk->role)->toBeNull();
        });

        it('creates chunk with all parameters', function () {
            $chunk = new StreamChunk(
                content: 'test content',
                role: 'assistant',
                finishReason: 'stop',
                model: 'gpt-4',
                inputTokens: 100,
                outputTokens: 50,
                toolCalls: [['name' => 'search']],
                raw: ['id' => 'chunk-123'],
            );

            expect($chunk->content)->toBe('test content');
            expect($chunk->role)->toBe('assistant');
            expect($chunk->finishReason)->toBe('stop');
            expect($chunk->model)->toBe('gpt-4');
            expect($chunk->inputTokens)->toBe(100);
            expect($chunk->outputTokens)->toBe(50);
            expect($chunk->toolCalls)->toBe([['name' => 'search']]);
            expect($chunk->raw)->toBe(['id' => 'chunk-123']);
        });
    });

    describe('Readonly Properties', function () {
        it('is a readonly class', function () {
            $reflection = new ReflectionClass(StreamChunk::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });
    });

    describe('hasContent()', function () {
        it('returns true for non-empty content', function () {
            $chunk = new StreamChunk(content: 'Hello');

            expect($chunk->hasContent())->toBeTrue();
        });

        it('returns false for null content', function () {
            $chunk = new StreamChunk(content: null);

            expect($chunk->hasContent())->toBeFalse();
        });

        it('returns false for empty string content', function () {
            $chunk = new StreamChunk(content: '');

            expect($chunk->hasContent())->toBeFalse();
        });

        it('returns true for whitespace content', function () {
            $chunk = new StreamChunk(content: ' ');

            expect($chunk->hasContent())->toBeTrue();
        });

        it('returns true for single character', function () {
            $chunk = new StreamChunk(content: 'a');

            expect($chunk->hasContent())->toBeTrue();
        });
    });

    describe('isComplete()', function () {
        it('returns true when finishReason is set', function () {
            $chunk = new StreamChunk(finishReason: 'stop');

            expect($chunk->isComplete())->toBeTrue();
        });

        it('returns false when finishReason is null', function () {
            $chunk = new StreamChunk(finishReason: null);

            expect($chunk->isComplete())->toBeFalse();
        });

        it('returns true for length finish reason', function () {
            $chunk = new StreamChunk(finishReason: 'length');

            expect($chunk->isComplete())->toBeTrue();
        });

        it('returns true for tool_calls finish reason', function () {
            $chunk = new StreamChunk(finishReason: 'tool_calls');

            expect($chunk->isComplete())->toBeTrue();
        });

        it('returns true for any non-null finish reason', function () {
            $chunk = new StreamChunk(finishReason: 'custom_reason');

            expect($chunk->isComplete())->toBeTrue();
        });
    });

    describe('hasToolCalls()', function () {
        it('returns true when toolCalls array has items', function () {
            $chunk = new StreamChunk(toolCalls: [
                ['name' => 'search', 'arguments' => []],
            ]);

            expect($chunk->hasToolCalls())->toBeTrue();
        });

        it('returns false when toolCalls is null', function () {
            $chunk = new StreamChunk(toolCalls: null);

            expect($chunk->hasToolCalls())->toBeFalse();
        });

        it('returns false when toolCalls is empty array', function () {
            $chunk = new StreamChunk(toolCalls: []);

            expect($chunk->hasToolCalls())->toBeFalse();
        });

        it('returns true for multiple tool calls', function () {
            $chunk = new StreamChunk(toolCalls: [
                ['name' => 'search'],
                ['name' => 'calculate'],
            ]);

            expect($chunk->hasToolCalls())->toBeTrue();
        });
    });

    describe('Content Types', function () {
        it('handles unicode content', function () {
            $chunk = new StreamChunk(content: '日本語 🎉');

            expect($chunk->content)->toBe('日本語 🎉');
            expect($chunk->hasContent())->toBeTrue();
        });

        it('handles multiline content', function () {
            $content = "Line 1\nLine 2\nLine 3";
            $chunk = new StreamChunk(content: $content);

            expect($chunk->content)->toBe($content);
        });

        it('handles special characters in content', function () {
            $chunk = new StreamChunk(content: 'Special chars: <>&"\'');

            expect($chunk->content)->toContain('<>&"\'');
        });

        it('handles very long content', function () {
            $content = str_repeat('Lorem ipsum ', 1000);
            $chunk = new StreamChunk(content: $content);

            expect($chunk->content)->toBe($content);
        });
    });

    describe('Token Counts', function () {
        it('handles zero token counts', function () {
            $chunk = new StreamChunk(
                inputTokens: 0,
                outputTokens: 0,
            );

            expect($chunk->inputTokens)->toBe(0);
            expect($chunk->outputTokens)->toBe(0);
        });

        it('handles large token counts', function () {
            $chunk = new StreamChunk(
                inputTokens: 128000,
                outputTokens: 4096,
            );

            expect($chunk->inputTokens)->toBe(128000);
            expect($chunk->outputTokens)->toBe(4096);
        });

        it('handles inputTokens only', function () {
            $chunk = new StreamChunk(inputTokens: 100);

            expect($chunk->inputTokens)->toBe(100);
            expect($chunk->outputTokens)->toBeNull();
        });

        it('handles outputTokens only', function () {
            $chunk = new StreamChunk(outputTokens: 50);

            expect($chunk->outputTokens)->toBe(50);
            expect($chunk->inputTokens)->toBeNull();
        });
    });

    describe('Roles', function () {
        it('handles user role', function () {
            $chunk = new StreamChunk(role: 'user');

            expect($chunk->role)->toBe('user');
        });

        it('handles assistant role', function () {
            $chunk = new StreamChunk(role: 'assistant');

            expect($chunk->role)->toBe('assistant');
        });

        it('handles system role', function () {
            $chunk = new StreamChunk(role: 'system');

            expect($chunk->role)->toBe('system');
        });

        it('handles custom roles', function () {
            $chunk = new StreamChunk(role: 'custom');

            expect($chunk->role)->toBe('custom');
        });
    });

    describe('Models', function () {
        it('handles OpenAI models', function () {
            $chunk = new StreamChunk(model: 'gpt-4-turbo');

            expect($chunk->model)->toBe('gpt-4-turbo');
        });

        it('handles Anthropic models', function () {
            $chunk = new StreamChunk(model: 'claude-3-5-sonnet-20241022');

            expect($chunk->model)->toBe('claude-3-5-sonnet-20241022');
        });

        it('handles custom model names', function () {
            $chunk = new StreamChunk(model: 'custom-model-v1');

            expect($chunk->model)->toBe('custom-model-v1');
        });
    });

    describe('Raw Data', function () {
        it('stores provider-specific data', function () {
            $raw = [
                'id' => 'chatcmpl-123',
                'object' => 'chat.completion.chunk',
                'created' => 1677652288,
            ];

            $chunk = new StreamChunk(raw: $raw);

            expect($chunk->raw)->toBe($raw);
        });

        it('handles empty raw data', function () {
            $chunk = new StreamChunk(raw: []);

            expect($chunk->raw)->toBe([]);
        });

        it('handles nested raw data', function () {
            $raw = [
                'delta' => [
                    'content' => 'text',
                    'role' => 'assistant',
                ],
            ];

            $chunk = new StreamChunk(raw: $raw);

            expect($chunk->raw['delta']['content'])->toBe('text');
        });
    });

    describe('Tool Calls', function () {
        it('handles single tool call', function () {
            $toolCalls = [
                [
                    'name' => 'get_weather',
                    'arguments' => ['location' => 'Paris'],
                ],
            ];

            $chunk = new StreamChunk(toolCalls: $toolCalls);

            expect($chunk->toolCalls)->toBe($toolCalls);
            expect($chunk->hasToolCalls())->toBeTrue();
        });

        it('handles multiple tool calls', function () {
            $toolCalls = [
                ['name' => 'search', 'arguments' => ['query' => 'test']],
                ['name' => 'calculate', 'arguments' => ['expression' => '2+2']],
            ];

            $chunk = new StreamChunk(toolCalls: $toolCalls);

            expect($chunk->toolCalls)->toHaveCount(2);
        });

        it('handles tool calls with complex arguments', function () {
            $toolCalls = [
                [
                    'name' => 'create_user',
                    'arguments' => [
                        'user' => [
                            'name' => 'John',
                            'email' => 'john@example.com',
                            'roles' => ['admin', 'user'],
                        ],
                    ],
                ],
            ];

            $chunk = new StreamChunk(toolCalls: $toolCalls);

            expect($chunk->toolCalls[0]['arguments']['user']['roles'])->toBe(['admin', 'user']);
        });
    });

    describe('Edge Cases', function () {
        it('handles chunk with only metadata', function () {
            $chunk = new StreamChunk(
                role: 'assistant',
                model: 'gpt-4',
            );

            expect($chunk->hasContent())->toBeFalse();
            expect($chunk->role)->toBe('assistant');
            expect($chunk->model)->toBe('gpt-4');
        });

        it('handles chunk with only content', function () {
            $chunk = new StreamChunk(content: 'text');

            expect($chunk->hasContent())->toBeTrue();
            expect($chunk->role)->toBeNull();
            expect($chunk->finishReason)->toBeNull();
        });

        it('handles final chunk with all data', function () {
            $chunk = new StreamChunk(
                content: '',
                role: 'assistant',
                finishReason: 'stop',
                model: 'gpt-4',
                inputTokens: 10,
                outputTokens: 20,
            );

            expect($chunk->isComplete())->toBeTrue();
            expect($chunk->hasContent())->toBeFalse();
        });

        it('handles chunk with negative token counts', function () {
            // Some providers might send weird data
            $chunk = new StreamChunk(
                inputTokens: -1,
                outputTokens: -1,
            );

            expect($chunk->inputTokens)->toBe(-1);
            expect($chunk->outputTokens)->toBe(-1);
        });
    });

    describe('Typical Streaming Scenarios', function () {
        it('simulates first chunk with role', function () {
            $chunk = new StreamChunk(
                content: '',
                role: 'assistant',
            );

            expect($chunk->role)->toBe('assistant');
            expect($chunk->hasContent())->toBeFalse();
            expect($chunk->isComplete())->toBeFalse();
        });

        it('simulates middle chunk with content', function () {
            $chunk = new StreamChunk(content: 'Hello ');

            expect($chunk->hasContent())->toBeTrue();
            expect($chunk->isComplete())->toBeFalse();
            expect($chunk->role)->toBeNull();
        });

        it('simulates last chunk with finish reason', function () {
            $chunk = new StreamChunk(
                content: '!',
                finishReason: 'stop',
                inputTokens: 10,
                outputTokens: 5,
            );

            expect($chunk->isComplete())->toBeTrue();
            expect($chunk->hasContent())->toBeTrue();
        });
    });
});
