<?php

use Mindwave\Mindwave\Contracts\Tool;
use Mindwave\Mindwave\Tools\SimpleTool;

describe('SimpleTool', function () {
    describe('Constructor', function () {
        it('accepts name, description, and callback', function () {
            $tool = new SimpleTool(
                name: 'test_tool',
                description: 'A test tool',
                callback: fn ($input) => "Processed: $input"
            );

            expect($tool)->toBeInstanceOf(SimpleTool::class);
        });
    });

    describe('Tool Interface', function () {
        it('implements Tool interface', function () {
            $tool = new SimpleTool(
                name: 'test',
                description: 'test',
                callback: fn ($input) => $input
            );

            expect($tool)->toBeInstanceOf(Tool::class);
        });

        it('returns configured name', function () {
            $tool = new SimpleTool(
                name: 'custom_name',
                description: 'test',
                callback: fn ($input) => $input
            );

            expect($tool->name())->toBe('custom_name');
        });

        it('returns configured description', function () {
            $tool = new SimpleTool(
                name: 'test',
                description: 'A detailed description of the tool',
                callback: fn ($input) => $input
            );

            expect($tool->description())->toBe('A detailed description of the tool');
        });
    });

    describe('run()', function () {
        it('executes callback with input', function () {
            $tool = new SimpleTool(
                name: 'echo',
                description: 'Echoes input',
                callback: fn ($input) => "Echo: $input"
            );

            $result = $tool->run('Hello');

            expect($result)->toBe('Echo: Hello');
        });

        it('passes $this context to callback', function () {
            $capturedContext = null;

            $tool = new SimpleTool(
                name: 'context_test',
                description: 'Tests context',
                callback: function ($input) use (&$capturedContext) {
                    $capturedContext = $this;
                    return $input;
                }
            );

            $tool->run('test');

            expect($capturedContext)->toBeInstanceOf(SimpleTool::class);
        });

        it('returns string result from callback', function () {
            $tool = new SimpleTool(
                name: 'uppercase',
                description: 'Makes uppercase',
                callback: fn ($input) => strtoupper($input)
            );

            $result = $tool->run('hello');

            expect($result)->toBe('HELLO');
        });

        it('handles complex input processing', function () {
            $tool = new SimpleTool(
                name: 'json_tool',
                description: 'Processes JSON',
                callback: function ($input) {
                    $data = json_decode($input, true);
                    return json_encode(['processed' => true, 'original' => $data]);
                }
            );

            $result = $tool->run('{"key": "value"}');
            $decoded = json_decode($result, true);

            expect($decoded['processed'])->toBeTrue();
            expect($decoded['original']['key'])->toBe('value');
        });

        it('handles empty input', function () {
            $tool = new SimpleTool(
                name: 'test',
                description: 'test',
                callback: fn ($input) => "Input was: '$input'"
            );

            $result = $tool->run('');

            expect($result)->toBe("Input was: ''");
        });
    });
});
