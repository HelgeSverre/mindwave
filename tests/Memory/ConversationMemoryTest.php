<?php

use Mindwave\Mindwave\Memory\ConversationMemory;
use Mindwave\Mindwave\Message\Role;

describe('ConversationMemory', function () {
    describe('Static Factory', function () {
        it('creates empty instance with make()', function () {
            $memory = ConversationMemory::make();

            expect($memory)->toBeInstanceOf(ConversationMemory::class);
            expect($memory->toArray())->toBe([]);
        });
    });

    describe('fromMessages()', function () {
        it('creates instance from array of messages', function () {
            $messages = [
                ['role' => 'system', 'content' => 'You are helpful'],
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'ai', 'content' => 'Hi there!'],
            ];

            $memory = ConversationMemory::fromMessages($messages);

            expect($memory->toArray())->toHaveCount(3);
        });

        it('correctly identifies system messages', function () {
            $messages = [
                ['role' => 'system', 'content' => 'System prompt'],
            ];

            $memory = ConversationMemory::fromMessages($messages);
            $array = $memory->toArray();

            expect($array[0]['role'])->toBe(Role::system);
        });

        it('correctly identifies ai messages', function () {
            $messages = [
                ['role' => 'ai', 'content' => 'AI response'],
            ];

            $memory = ConversationMemory::fromMessages($messages);
            $array = $memory->toArray();

            expect($array[0]['role'])->toBe(Role::ai);
        });

        it('correctly identifies user messages', function () {
            $messages = [
                ['role' => 'user', 'content' => 'User input'],
            ];

            $memory = ConversationMemory::fromMessages($messages);
            $array = $memory->toArray();

            expect($array[0]['role'])->toBe(Role::user);
        });

        it('preserves meta data from messages', function () {
            $messages = [
                ['role' => 'user', 'content' => 'Hello', 'meta' => ['id' => 123]],
            ];

            $memory = ConversationMemory::fromMessages($messages);
            $array = $memory->toArray();

            expect($array[0]['meta'])->toBe(['id' => 123]);
        });
    });

    describe('Add Message Methods', function () {
        it('adds system message with addSystemMessage()', function () {
            $memory = ConversationMemory::make();
            $memory->addSystemMessage('You are a helpful assistant');

            $array = $memory->toArray();
            expect($array)->toHaveCount(1);
            expect($array[0]['role'])->toBe(Role::system);
            expect($array[0]['content'])->toBe('You are a helpful assistant');
        });

        it('adds ai message with addAiMessage()', function () {
            $memory = ConversationMemory::make();
            $memory->addAiMessage('Hello, how can I help?');

            $array = $memory->toArray();
            expect($array)->toHaveCount(1);
            expect($array[0]['role'])->toBe(Role::ai);
            expect($array[0]['content'])->toBe('Hello, how can I help?');
        });

        it('adds user message with addUserMessage()', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage('I need help with coding');

            $array = $memory->toArray();
            expect($array)->toHaveCount(1);
            expect($array[0]['role'])->toBe(Role::user);
            expect($array[0]['content'])->toBe('I need help with coding');
        });

        it('accepts optional meta array for messages', function () {
            $memory = ConversationMemory::make();
            $meta = ['timestamp' => 1234567890, 'source' => 'test'];
            $memory->addUserMessage('Hello', $meta);

            $array = $memory->toArray();
            expect($array[0]['meta'])->toBe($meta);
        });

        it('maintains message order', function () {
            $memory = ConversationMemory::make();
            $memory->addSystemMessage('System');
            $memory->addUserMessage('User');
            $memory->addAiMessage('AI');

            $array = $memory->toArray();
            expect($array[0]['content'])->toBe('System');
            expect($array[1]['content'])->toBe('User');
            expect($array[2]['content'])->toBe('AI');
        });
    });

    describe('conversationAsString()', function () {
        it('formats messages with default prefixes', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage('Hello');
            $memory->addAiMessage('Hi there!');

            $string = $memory->conversationAsString();

            expect($string)->toContain('Human: Hello');
            expect($string)->toContain('AI: Hi there!');
        });

        it('formats messages with custom prefixes', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage('Hello');
            $memory->addAiMessage('Hi there!');
            $memory->addSystemMessage('Be helpful');

            $string = $memory->conversationAsString(
                humanPrefix: 'User',
                aiPrefix: 'Assistant',
                systemPrefix: 'Instructions'
            );

            expect($string)->toContain('User: Hello');
            expect($string)->toContain('Assistant: Hi there!');
            expect($string)->toContain('Instructions: Be helpful');
        });

        it('joins messages with newlines', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage('First');
            $memory->addAiMessage('Second');

            $string = $memory->conversationAsString();
            $lines = explode("\n", $string);

            expect($lines)->toHaveCount(2);
        });

        it('returns empty string for empty memory', function () {
            $memory = ConversationMemory::make();

            expect($memory->conversationAsString())->toBe('');
        });
    });

    describe('clear()', function () {
        it('removes all messages', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage('Hello');
            $memory->addAiMessage('Hi');
            $memory->addSystemMessage('System');

            expect($memory->toArray())->toHaveCount(3);

            $memory->clear();

            expect($memory->toArray())->toBe([]);
        });
    });

    describe('toArray()', function () {
        it('converts all messages to array format', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage('Hello');
            $memory->addAiMessage('Hi');

            $array = $memory->toArray();

            expect($array)->toBeArray();
            expect($array)->toHaveCount(2);
            expect($array[0])->toHaveKeys(['role', 'content', 'meta']);
        });

        it('preserves message order', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage('First');
            $memory->addAiMessage('Second');
            $memory->addUserMessage('Third');

            $array = $memory->toArray();

            expect($array[0]['content'])->toBe('First');
            expect($array[1]['content'])->toBe('Second');
            expect($array[2]['content'])->toBe('Third');
        });

        it('returns empty array when no messages', function () {
            $memory = ConversationMemory::make();

            expect($memory->toArray())->toBe([]);
        });
    });

    describe('Edge Cases', function () {
        it('handles large number of messages', function () {
            $memory = ConversationMemory::make();

            for ($i = 0; $i < 100; $i++) {
                $memory->addUserMessage("Message {$i}");
            }

            expect($memory->toArray())->toHaveCount(100);
        });

        it('handles empty content messages', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage('');

            $array = $memory->toArray();
            expect($array[0]['content'])->toBe('');
        });

        it('handles multiline message content', function () {
            $memory = ConversationMemory::make();
            $memory->addUserMessage("Line 1\nLine 2\nLine 3");

            $array = $memory->toArray();
            expect($array[0]['content'])->toBe("Line 1\nLine 2\nLine 3");
        });
    });
});
