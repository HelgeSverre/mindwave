<?php

declare(strict_types=1);

use Mindwave\Mindwave\LLM\Drivers\Fake;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionCall;
use Mindwave\Mindwave\LLM\Responses\ChatResponse;

describe('Fake Driver', function () {
    beforeEach(function () {
        $this->driver = new Fake;
    });

    describe('respondsWith', function () {
        it('sets the response text', function () {
            $this->driver->respondsWith('Hello, world!');

            $result = $this->driver->generateText('Any prompt');

            expect($result)->toBe('Hello, world!');
        });

        it('returns self for chaining', function () {
            $result = $this->driver->respondsWith('test');

            expect($result)->toBe($this->driver);
        });
    });

    describe('generateText', function () {
        it('returns empty string by default', function () {
            $result = $this->driver->generateText('Any prompt');

            expect($result)->toBe('');
        });

        it('ignores the prompt and returns configured response', function () {
            $this->driver->respondsWith('Fixed response');

            expect($this->driver->generateText('Prompt 1'))->toBe('Fixed response');
            expect($this->driver->generateText('Prompt 2'))->toBe('Fixed response');
            expect($this->driver->generateText('Different prompt'))->toBe('Fixed response');
        });

        it('returns unicode content correctly', function () {
            $this->driver->respondsWith('日本語テキスト 🎉');

            $result = $this->driver->generateText('test');

            expect($result)->toBe('日本語テキスト 🎉');
        });
    });

    describe('streamText', function () {
        it('yields response in chunks', function () {
            $this->driver->respondsWith('Hello, world!');

            $chunks = iterator_to_array($this->driver->streamText('prompt'));

            expect($chunks)->toBe(['Hello', ', wor', 'ld!']);
        });

        it('uses default chunk size of 5', function () {
            $this->driver->respondsWith('ABCDEFGHIJ');

            $chunks = iterator_to_array($this->driver->streamText('prompt'));

            expect($chunks)->toBe(['ABCDE', 'FGHIJ']);
        });

        it('allows custom chunk size', function () {
            $this->driver->respondsWith('ABCDEFGHIJ')->streamChunkSize(2);

            $chunks = iterator_to_array($this->driver->streamText('prompt'));

            expect($chunks)->toBe(['AB', 'CD', 'EF', 'GH', 'IJ']);
        });

        it('handles empty response', function () {
            $this->driver->respondsWith('');

            $chunks = iterator_to_array($this->driver->streamText('prompt'));

            expect($chunks)->toBe([]);
        });

        it('handles unicode content correctly', function () {
            $this->driver->respondsWith('日本語テキスト')->streamChunkSize(3);

            $chunks = iterator_to_array($this->driver->streamText('prompt'));

            expect($chunks)->toBe(['日本語', 'テキス', 'ト']);
        });

        it('handles chunk size larger than response', function () {
            $this->driver->respondsWith('Hi')->streamChunkSize(100);

            $chunks = iterator_to_array($this->driver->streamText('prompt'));

            expect($chunks)->toBe(['Hi']);
        });
    });

    describe('streamChunkSize', function () {
        it('returns self for chaining', function () {
            $result = $this->driver->streamChunkSize(10);

            expect($result)->toBe($this->driver);
        });
    });

    describe('chat', function () {
        it('returns ChatResponse instance', function () {
            $this->driver->respondsWith('Chat response');

            $response = $this->driver->chat([
                ['role' => 'user', 'content' => 'Hello'],
            ]);

            expect($response)->toBeInstanceOf(ChatResponse::class);
            expect($response->content)->toBe('Chat response');
        });

        it('returns assistant role', function () {
            $response = $this->driver->chat([]);

            expect($response->role)->toBe('assistant');
        });

        it('returns fixed token counts', function () {
            $response = $this->driver->chat([]);

            expect($response->inputTokens)->toBe(10);
            expect($response->outputTokens)->toBe(10);
        });

        it('returns stop finish reason', function () {
            $response = $this->driver->chat([]);

            expect($response->finishReason)->toBe('stop');
        });

        it('returns fake-model as model', function () {
            $response = $this->driver->chat([]);

            expect($response->model)->toBe('fake-model');
        });

        it('returns empty raw array', function () {
            $response = $this->driver->chat([]);

            expect($response->raw)->toBe([]);
        });
    });

    describe('functionCall', function () {
        it('returns FunctionCall instance', function () {
            $result = $this->driver->functionCall('prompt', []);

            expect($result)->toBeInstanceOf(FunctionCall::class);
        });

        it('returns fake function name', function () {
            $result = $this->driver->functionCall('prompt', []);

            expect($result->name)->toBe('fake_function');
        });

        it('returns example arguments', function () {
            $result = $this->driver->functionCall('prompt', []);

            expect($result->arguments)->toBe(['example function call response']);
        });

        it('returns raw arguments as JSON', function () {
            $result = $this->driver->functionCall('prompt', []);

            expect($result->rawArguments)->toBe('["example function call response"]');
        });

        it('ignores prompt parameter', function () {
            $result1 = $this->driver->functionCall('prompt 1', []);
            $result2 = $this->driver->functionCall('completely different', []);

            expect($result1->name)->toBe($result2->name);
        });
    });

    describe('getModel', function () {
        it('returns fake-model', function () {
            expect($this->driver->getModel())->toBe('fake-model');
        });
    });
});
