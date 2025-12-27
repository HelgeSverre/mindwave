<?php

declare(strict_types=1);

use Mindwave\Mindwave\LLM\FunctionCalling\FunctionCall;

describe('FunctionCall', function () {
    describe('Construction', function () {
        it('creates function call with all parameters', function () {
            $call = new FunctionCall(
                name: 'search_web',
                arguments: ['query' => 'weather'],
                rawArguments: '{"query": "weather"}',
            );

            expect($call->name)->toBe('search_web');
            expect($call->arguments)->toBe(['query' => 'weather']);
            expect($call->rawArguments)->toBe('{"query": "weather"}');
        });
    });

    describe('Readonly Properties', function () {
        it('is a readonly class', function () {
            $reflection = new ReflectionClass(FunctionCall::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });
    });

    describe('Function Name', function () {
        it('handles simple function names', function () {
            $call = new FunctionCall(
                name: 'get_weather',
                arguments: [],
                rawArguments: '{}',
            );

            expect($call->name)->toBe('get_weather');
        });

        it('handles namespaced function names', function () {
            $call = new FunctionCall(
                name: 'tools.search.web',
                arguments: [],
                rawArguments: '{}',
            );

            expect($call->name)->toBe('tools.search.web');
        });

        it('handles snake_case names', function () {
            $call = new FunctionCall(
                name: 'calculate_shipping_cost',
                arguments: [],
                rawArguments: '{}',
            );

            expect($call->name)->toBe('calculate_shipping_cost');
        });
    });

    describe('Arguments', function () {
        it('handles empty arguments', function () {
            $call = new FunctionCall(
                name: 'ping',
                arguments: [],
                rawArguments: '{}',
            );

            expect($call->arguments)->toBe([]);
        });

        it('handles string arguments', function () {
            $call = new FunctionCall(
                name: 'greet',
                arguments: ['name' => 'John'],
                rawArguments: '{"name": "John"}',
            );

            expect($call->arguments['name'])->toBe('John');
        });

        it('handles numeric arguments', function () {
            $call = new FunctionCall(
                name: 'calculate',
                arguments: ['a' => 10, 'b' => 20],
                rawArguments: '{"a": 10, "b": 20}',
            );

            expect($call->arguments['a'])->toBe(10);
            expect($call->arguments['b'])->toBe(20);
        });

        it('handles boolean arguments', function () {
            $call = new FunctionCall(
                name: 'toggle',
                arguments: ['enabled' => true, 'visible' => false],
                rawArguments: '{"enabled": true, "visible": false}',
            );

            expect($call->arguments['enabled'])->toBeTrue();
            expect($call->arguments['visible'])->toBeFalse();
        });

        it('handles array arguments', function () {
            $call = new FunctionCall(
                name: 'process_items',
                arguments: ['items' => ['a', 'b', 'c']],
                rawArguments: '{"items": ["a", "b", "c"]}',
            );

            expect($call->arguments['items'])->toBe(['a', 'b', 'c']);
        });

        it('handles nested object arguments', function () {
            $call = new FunctionCall(
                name: 'create_user',
                arguments: [
                    'user' => [
                        'name' => 'John',
                        'email' => 'john@example.com',
                    ],
                ],
                rawArguments: '{"user": {"name": "John", "email": "john@example.com"}}',
            );

            expect($call->arguments['user']['name'])->toBe('John');
            expect($call->arguments['user']['email'])->toBe('john@example.com');
        });

        it('handles null arguments', function () {
            $call = new FunctionCall(
                name: 'optional',
                arguments: ['value' => null],
                rawArguments: '{"value": null}',
            );

            expect($call->arguments['value'])->toBeNull();
        });
    });

    describe('Raw Arguments', function () {
        it('preserves original JSON string', function () {
            $raw = '{"key": "value", "number": 42}';
            $call = new FunctionCall(
                name: 'test',
                arguments: ['key' => 'value', 'number' => 42],
                rawArguments: $raw,
            );

            expect($call->rawArguments)->toBe($raw);
        });

        it('handles malformed JSON in raw', function () {
            // Sometimes LLMs return invalid JSON which we still want to capture
            $call = new FunctionCall(
                name: 'test',
                arguments: [],
                rawArguments: '{invalid json',
            );

            expect($call->rawArguments)->toBe('{invalid json');
        });

        it('handles unicode in raw arguments', function () {
            $raw = '{"message": "日本語"}';
            $call = new FunctionCall(
                name: 'test',
                arguments: ['message' => '日本語'],
                rawArguments: $raw,
            );

            expect($call->rawArguments)->toBe($raw);
        });
    });
});
