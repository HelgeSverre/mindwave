<?php

declare(strict_types=1);

use Mindwave\Mindwave\LLM\Responses\ChatResponseMetadata;

describe('ChatResponseMetadata', function () {
    describe('Construction', function () {
        it('creates with default values', function () {
            $metadata = new ChatResponseMetadata;

            expect($metadata->role)->toBeNull();
            expect($metadata->finishReason)->toBeNull();
            expect($metadata->model)->toBeNull();
            expect($metadata->inputTokens)->toBeNull();
            expect($metadata->outputTokens)->toBeNull();
            expect($metadata->totalTokens)->toBeNull();
            expect($metadata->content)->toBe('');
            expect($metadata->toolCalls)->toBe([]);
        });

        it('creates with all parameters', function () {
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                finishReason: 'stop',
                model: 'gpt-4',
                inputTokens: 100,
                outputTokens: 50,
                totalTokens: 150,
                content: 'Hello, world!',
                toolCalls: [['name' => 'search']],
            );

            expect($metadata->role)->toBe('assistant');
            expect($metadata->finishReason)->toBe('stop');
            expect($metadata->model)->toBe('gpt-4');
            expect($metadata->inputTokens)->toBe(100);
            expect($metadata->outputTokens)->toBe(50);
            expect($metadata->totalTokens)->toBe(150);
            expect($metadata->content)->toBe('Hello, world!');
            expect($metadata->toolCalls)->toBe([['name' => 'search']]);
        });

        it('creates with partial data', function () {
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                content: 'Response text',
            );

            expect($metadata->role)->toBe('assistant');
            expect($metadata->content)->toBe('Response text');
            expect($metadata->finishReason)->toBeNull();
            expect($metadata->inputTokens)->toBeNull();
        });
    });

    describe('Readonly Properties', function () {
        it('is a readonly class', function () {
            $reflection = new ReflectionClass(ChatResponseMetadata::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });
    });

    describe('Content', function () {
        it('handles empty content', function () {
            $metadata = new ChatResponseMetadata(content: '');

            expect($metadata->content)->toBe('');
        });

        it('handles long content', function () {
            $content = str_repeat('Lorem ipsum dolor sit amet. ', 1000);
            $metadata = new ChatResponseMetadata(content: $content);

            expect($metadata->content)->toBe($content);
        });

        it('handles unicode content', function () {
            $metadata = new ChatResponseMetadata(content: '日本語テキスト 🎉');

            expect($metadata->content)->toBe('日本語テキスト 🎉');
        });

        it('handles multiline content', function () {
            $content = "Line 1\nLine 2\nLine 3";
            $metadata = new ChatResponseMetadata(content: $content);

            expect($metadata->content)->toBe($content);
        });

        it('handles special characters', function () {
            $metadata = new ChatResponseMetadata(content: 'Special: <>&"\'');

            expect($metadata->content)->toContain('<>&"\'');
        });
    });

    describe('Roles', function () {
        it('handles assistant role', function () {
            $metadata = new ChatResponseMetadata(role: 'assistant');

            expect($metadata->role)->toBe('assistant');
        });

        it('handles user role', function () {
            $metadata = new ChatResponseMetadata(role: 'user');

            expect($metadata->role)->toBe('user');
        });

        it('handles system role', function () {
            $metadata = new ChatResponseMetadata(role: 'system');

            expect($metadata->role)->toBe('system');
        });

        it('handles null role', function () {
            $metadata = new ChatResponseMetadata(role: null);

            expect($metadata->role)->toBeNull();
        });
    });

    describe('Finish Reasons', function () {
        it('handles stop finish reason', function () {
            $metadata = new ChatResponseMetadata(finishReason: 'stop');

            expect($metadata->finishReason)->toBe('stop');
        });

        it('handles length finish reason', function () {
            $metadata = new ChatResponseMetadata(finishReason: 'length');

            expect($metadata->finishReason)->toBe('length');
        });

        it('handles tool_calls finish reason', function () {
            $metadata = new ChatResponseMetadata(finishReason: 'tool_calls');

            expect($metadata->finishReason)->toBe('tool_calls');
        });

        it('handles content_filter finish reason', function () {
            $metadata = new ChatResponseMetadata(finishReason: 'content_filter');

            expect($metadata->finishReason)->toBe('content_filter');
        });

        it('handles null finish reason', function () {
            $metadata = new ChatResponseMetadata(finishReason: null);

            expect($metadata->finishReason)->toBeNull();
        });
    });

    describe('Models', function () {
        it('handles OpenAI GPT-4 model', function () {
            $metadata = new ChatResponseMetadata(model: 'gpt-4-turbo');

            expect($metadata->model)->toBe('gpt-4-turbo');
        });

        it('handles Anthropic Claude model', function () {
            $metadata = new ChatResponseMetadata(model: 'claude-3-5-sonnet-20241022');

            expect($metadata->model)->toBe('claude-3-5-sonnet-20241022');
        });

        it('handles null model', function () {
            $metadata = new ChatResponseMetadata(model: null);

            expect($metadata->model)->toBeNull();
        });
    });

    describe('Token Counts', function () {
        it('calculates total tokens', function () {
            $metadata = new ChatResponseMetadata(
                inputTokens: 100,
                outputTokens: 50,
                totalTokens: 150,
            );

            expect($metadata->totalTokens)->toBe(150);
        });

        it('handles zero token counts', function () {
            $metadata = new ChatResponseMetadata(
                inputTokens: 0,
                outputTokens: 0,
                totalTokens: 0,
            );

            expect($metadata->inputTokens)->toBe(0);
            expect($metadata->outputTokens)->toBe(0);
            expect($metadata->totalTokens)->toBe(0);
        });

        it('handles large token counts', function () {
            $metadata = new ChatResponseMetadata(
                inputTokens: 128000,
                outputTokens: 4096,
                totalTokens: 132096,
            );

            expect($metadata->inputTokens)->toBe(128000);
            expect($metadata->outputTokens)->toBe(4096);
            expect($metadata->totalTokens)->toBe(132096);
        });

        it('handles null token counts', function () {
            $metadata = new ChatResponseMetadata(
                inputTokens: null,
                outputTokens: null,
                totalTokens: null,
            );

            expect($metadata->inputTokens)->toBeNull();
            expect($metadata->outputTokens)->toBeNull();
            expect($metadata->totalTokens)->toBeNull();
        });

        it('handles partial token data', function () {
            $metadata = new ChatResponseMetadata(
                inputTokens: 100,
                outputTokens: null,
                totalTokens: null,
            );

            expect($metadata->inputTokens)->toBe(100);
            expect($metadata->outputTokens)->toBeNull();
            expect($metadata->totalTokens)->toBeNull();
        });
    });

    describe('Tool Calls', function () {
        it('handles empty tool calls array', function () {
            $metadata = new ChatResponseMetadata(toolCalls: []);

            expect($metadata->toolCalls)->toBe([]);
        });

        it('handles single tool call', function () {
            $toolCalls = [
                [
                    'name' => 'get_weather',
                    'arguments' => ['location' => 'Paris'],
                ],
            ];

            $metadata = new ChatResponseMetadata(toolCalls: $toolCalls);

            expect($metadata->toolCalls)->toBe($toolCalls);
            expect($metadata->toolCalls)->toHaveCount(1);
        });

        it('handles multiple tool calls', function () {
            $toolCalls = [
                ['name' => 'search', 'arguments' => ['query' => 'test']],
                ['name' => 'calculate', 'arguments' => ['expr' => '2+2']],
                ['name' => 'translate', 'arguments' => ['text' => 'hello', 'to' => 'es']],
            ];

            $metadata = new ChatResponseMetadata(toolCalls: $toolCalls);

            expect($metadata->toolCalls)->toHaveCount(3);
        });

        it('handles tool calls with complex arguments', function () {
            $toolCalls = [
                [
                    'name' => 'create_user',
                    'arguments' => [
                        'user' => [
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'metadata' => [
                                'roles' => ['admin', 'user'],
                                'settings' => ['theme' => 'dark'],
                            ],
                        ],
                    ],
                ],
            ];

            $metadata = new ChatResponseMetadata(toolCalls: $toolCalls);

            expect($metadata->toolCalls[0]['arguments']['user']['metadata']['roles'])
                ->toBe(['admin', 'user']);
        });
    });

    describe('Complete Metadata Examples', function () {
        it('represents successful completion', function () {
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                finishReason: 'stop',
                model: 'gpt-4',
                inputTokens: 50,
                outputTokens: 100,
                totalTokens: 150,
                content: 'This is the complete response from the assistant.',
                toolCalls: [],
            );

            expect($metadata->role)->toBe('assistant');
            expect($metadata->finishReason)->toBe('stop');
            expect($metadata->content)->not->toBeEmpty();
            expect($metadata->toolCalls)->toBeEmpty();
        });

        it('represents tool call response', function () {
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                finishReason: 'tool_calls',
                model: 'gpt-4',
                inputTokens: 30,
                outputTokens: 20,
                totalTokens: 50,
                content: '',
                toolCalls: [
                    ['name' => 'get_weather', 'arguments' => ['location' => 'London']],
                ],
            );

            expect($metadata->finishReason)->toBe('tool_calls');
            expect($metadata->content)->toBe('');
            expect($metadata->toolCalls)->toHaveCount(1);
        });

        it('represents length-limited response', function () {
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                finishReason: 'length',
                model: 'gpt-4',
                inputTokens: 100,
                outputTokens: 4096,
                totalTokens: 4196,
                content: str_repeat('Text ', 1000),
                toolCalls: [],
            );

            expect($metadata->finishReason)->toBe('length');
            expect($metadata->outputTokens)->toBe(4096);
        });
    });

    describe('Edge Cases', function () {
        it('handles metadata with only content', function () {
            $metadata = new ChatResponseMetadata(
                content: 'Just some text',
            );

            expect($metadata->content)->toBe('Just some text');
            expect($metadata->role)->toBeNull();
            expect($metadata->inputTokens)->toBeNull();
        });

        it('handles metadata with only token counts', function () {
            $metadata = new ChatResponseMetadata(
                inputTokens: 10,
                outputTokens: 20,
                totalTokens: 30,
            );

            expect($metadata->inputTokens)->toBe(10);
            expect($metadata->content)->toBe('');
        });

        it('handles metadata from incomplete stream', function () {
            // Stream might be interrupted
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                content: 'Incomplete respon',
                inputTokens: 10,
            );

            expect($metadata->finishReason)->toBeNull();
            expect($metadata->outputTokens)->toBeNull();
        });
    });

    describe('Real-world Scenarios', function () {
        it('represents OpenAI streaming completion', function () {
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                finishReason: 'stop',
                model: 'gpt-4-turbo-2024-04-09',
                inputTokens: 128,
                outputTokens: 256,
                totalTokens: 384,
                content: 'Here is a detailed explanation of quantum computing...',
                toolCalls: [],
            );

            expect($metadata->model)->toContain('gpt-4');
            expect($metadata->totalTokens)->toBe(384);
        });

        it('represents Anthropic streaming completion', function () {
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                finishReason: 'end_turn',
                model: 'claude-3-5-sonnet-20241022',
                inputTokens: 200,
                outputTokens: 500,
                totalTokens: 700,
                content: 'Let me help you understand this concept...',
                toolCalls: [],
            );

            expect($metadata->model)->toContain('claude');
            expect($metadata->finishReason)->toBe('end_turn');
        });

        it('represents multi-tool execution', function () {
            $metadata = new ChatResponseMetadata(
                role: 'assistant',
                finishReason: 'tool_calls',
                model: 'gpt-4',
                inputTokens: 150,
                outputTokens: 80,
                totalTokens: 230,
                content: '',
                toolCalls: [
                    ['name' => 'search_web', 'arguments' => ['query' => 'AI news']],
                    ['name' => 'get_weather', 'arguments' => ['location' => 'NYC']],
                    ['name' => 'calculate', 'arguments' => ['expr' => '15 * 24']],
                ],
            );

            expect($metadata->toolCalls)->toHaveCount(3);
            expect($metadata->content)->toBeEmpty();
        });
    });
});
