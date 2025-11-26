<?php

use Mindwave\Mindwave\Message\ChatMessage;
use Mindwave\Mindwave\Message\Role;

describe('ChatMessage', function () {
    describe('Factory Methods', function () {
        it('creates ai message with makeAiMessage()', function () {
            $message = ChatMessage::makeAiMessage('Hello from AI');

            expect($message->role())->toBe('ai');
            expect($message->content())->toBe('Hello from AI');
            expect($message->meta())->toBe([]);
        });

        it('creates user message with makeUserMessage()', function () {
            $message = ChatMessage::makeUserMessage('Hello from user');

            expect($message->role())->toBe('user');
            expect($message->content())->toBe('Hello from user');
            expect($message->meta())->toBe([]);
        });

        it('creates system message with makeSystemMessage()', function () {
            $message = ChatMessage::makeSystemMessage('You are a helpful assistant');

            expect($message->role())->toBe('system');
            expect($message->content())->toBe('You are a helpful assistant');
            expect($message->meta())->toBe([]);
        });

        it('accepts optional meta array', function () {
            $meta = ['source' => 'test', 'timestamp' => 1234567890];
            $message = ChatMessage::makeUserMessage('Hello', $meta);

            expect($message->meta())->toBe($meta);
        });

        it('handles null meta as empty array', function () {
            $message = ChatMessage::makeUserMessage('Hello', null);

            expect($message->meta())->toBeNull();
        });
    });

    describe('Accessors', function () {
        it('returns role value string', function () {
            $message = ChatMessage::makeSystemMessage('test');

            expect($message->role())->toBeString();
            expect($message->role())->toBe('system');
        });

        it('returns content string', function () {
            $content = 'This is the message content';
            $message = ChatMessage::makeUserMessage($content);

            expect($message->content())->toBe($content);
        });

        it('returns meta array or null', function () {
            $withMeta = ChatMessage::makeUserMessage('test', ['key' => 'value']);
            $withoutMeta = ChatMessage::makeUserMessage('test');

            expect($withMeta->meta())->toBe(['key' => 'value']);
            expect($withoutMeta->meta())->toBe([]);
        });
    });

    describe('Serialization', function () {
        it('converts to array with toArray()', function () {
            $message = ChatMessage::makeUserMessage('Hello', ['source' => 'test']);
            $array = $message->toArray();

            expect($array)->toBeArray();
            expect($array)->toHaveKeys(['role', 'content', 'meta']);
            expect($array['role'])->toBe(Role::user);
            expect($array['content'])->toBe('Hello');
            expect($array['meta'])->toBe(['source' => 'test']);
        });

        it('creates from array with fromArray()', function () {
            $original = ChatMessage::makeAiMessage('Test response', ['id' => 123]);
            $data = [
                'role' => 'ai',
                'content' => 'Test response',
                'meta' => ['id' => 123],
            ];

            $recreated = $original->fromArray($data);

            expect($recreated->role())->toBe('ai');
            expect($recreated->content())->toBe('Test response');
            expect($recreated->meta())->toBe(['id' => 123]);
        });
    });

    describe('Immutability', function () {
        it('is a readonly class', function () {
            $reflection = new ReflectionClass(ChatMessage::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });
    });

    describe('Edge Cases', function () {
        it('handles empty content string', function () {
            $message = ChatMessage::makeUserMessage('');

            expect($message->content())->toBe('');
        });

        it('handles multiline content', function () {
            $content = "Line 1\nLine 2\nLine 3";
            $message = ChatMessage::makeUserMessage($content);

            expect($message->content())->toBe($content);
        });

        it('handles unicode content', function () {
            $content = 'Hello 你好 مرحبا 🎉';
            $message = ChatMessage::makeUserMessage($content);

            expect($message->content())->toBe($content);
        });

        it('handles complex nested meta', function () {
            $meta = [
                'nested' => [
                    'deep' => [
                        'value' => 'test',
                    ],
                ],
                'array' => [1, 2, 3],
            ];
            $message = ChatMessage::makeUserMessage('test', $meta);

            expect($message->meta())->toBe($meta);
        });
    });
});
