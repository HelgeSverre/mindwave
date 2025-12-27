<?php

declare(strict_types=1);

use Mindwave\Mindwave\LLM\Responses\ChatResponse;
use Mindwave\Mindwave\LLM\Responses\ChatResponseMetadata;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use Mindwave\Mindwave\LLM\Responses\StreamedChatResponse;

describe('StreamedChatResponse', function () {
    function createMockStream(array $chunks): Generator
    {
        foreach ($chunks as $chunk) {
            yield $chunk;
        }
    }

    describe('Construction', function () {
        it('creates from generator', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'Hello'),
            ]);

            $response = new StreamedChatResponse($stream);

            expect($response)->toBeInstanceOf(StreamedChatResponse::class);
        });

        it('does not consume stream on construction', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);

            expect($response->isConsumed())->toBeFalse();
        });
    });

    describe('chunks()', function () {
        it('yields stream chunks', function () {
            $chunks = [
                new StreamChunk(content: 'Hello'),
                new StreamChunk(content: ' '),
                new StreamChunk(content: 'World'),
            ];

            $stream = createMockStream($chunks);
            $response = new StreamedChatResponse($stream);

            $yielded = [];
            foreach ($response->chunks() as $chunk) {
                $yielded[] = $chunk;
            }

            expect($yielded)->toHaveCount(3);
            expect($yielded[0]->content)->toBe('Hello');
            expect($yielded[1]->content)->toBe(' ');
            expect($yielded[2]->content)->toBe('World');
        });

        it('accumulates content from chunks', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'Hello'),
                new StreamChunk(content: ' '),
                new StreamChunk(content: 'World'),
            ]);

            $response = new StreamedChatResponse($stream);

            // Consume the stream
            foreach ($response->chunks() as $chunk) {
                // Just iterate
            }

            expect($response->getText())->toBe('Hello World');
        });

        it('updates metadata from chunks', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant'),
                new StreamChunk(content: 'Hi'),
                new StreamChunk(finishReason: 'stop', inputTokens: 10, outputTokens: 5),
            ]);

            $response = new StreamedChatResponse($stream);

            foreach ($response->chunks() as $chunk) {
                // Just iterate
            }

            $metadata = $response->getMetadata();
            expect($metadata->role)->toBe('assistant');
            expect($metadata->finishReason)->toBe('stop');
            expect($metadata->inputTokens)->toBe(10);
            expect($metadata->outputTokens)->toBe(5);
        });

        it('throws error when stream already consumed', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);

            // First consumption
            foreach ($response->chunks() as $chunk) {
                // Iterate
            }

            // Second consumption should throw
            expect(fn () => iterator_to_array($response->chunks()))
                ->toThrow(RuntimeException::class, 'Stream has already been consumed');
        });

        it('marks stream as consumed', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);

            expect($response->isConsumed())->toBeFalse();

            foreach ($response->chunks() as $chunk) {
                // Iterate
            }

            expect($response->isConsumed())->toBeTrue();
        });

        it('skips empty content chunks in accumulation', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'Hello'),
                new StreamChunk(content: ''),
                new StreamChunk(content: null),
                new StreamChunk(content: 'World'),
            ]);

            $response = new StreamedChatResponse($stream);

            foreach ($response->chunks() as $chunk) {
                // Iterate
            }

            expect($response->getText())->toBe('HelloWorld');
        });

        it('merges tool calls from chunks', function () {
            $stream = createMockStream([
                new StreamChunk(toolCalls: [['name' => 'search']]),
                new StreamChunk(toolCalls: [['name' => 'calculate']]),
            ]);

            $response = new StreamedChatResponse($stream);

            foreach ($response->chunks() as $chunk) {
                // Iterate
            }

            $metadata = $response->getMetadata();
            expect($metadata->toolCalls)->toHaveCount(2);
            expect($metadata->toolCalls[0]['name'])->toBe('search');
            expect($metadata->toolCalls[1]['name'])->toBe('calculate');
        });

        it('updates metadata progressively', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant', model: 'gpt-4'),
                new StreamChunk(content: 'Hello'),
                new StreamChunk(inputTokens: 10),
                new StreamChunk(outputTokens: 20),
                new StreamChunk(finishReason: 'stop'),
            ]);

            $response = new StreamedChatResponse($stream);

            foreach ($response->chunks() as $chunk) {
                // Iterate
            }

            $metadata = $response->getMetadata();
            expect($metadata->role)->toBe('assistant');
            expect($metadata->model)->toBe('gpt-4');
            expect($metadata->inputTokens)->toBe(10);
            expect($metadata->outputTokens)->toBe(20);
            expect($metadata->finishReason)->toBe('stop');
        });
    });

    describe('getText()', function () {
        it('returns accumulated content', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'Hello'),
                new StreamChunk(content: ' '),
                new StreamChunk(content: 'World'),
            ]);

            $response = new StreamedChatResponse($stream);
            $text = $response->getText();

            expect($text)->toBe('Hello World');
        });

        it('consumes stream if not already consumed', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);

            expect($response->isConsumed())->toBeFalse();

            $response->getText();

            expect($response->isConsumed())->toBeTrue();
        });

        it('returns empty string for no content', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant'),
                new StreamChunk(finishReason: 'stop'),
            ]);

            $response = new StreamedChatResponse($stream);
            $text = $response->getText();

            expect($text)->toBe('');
        });

        it('handles unicode content', function () {
            $stream = createMockStream([
                new StreamChunk(content: '日本語'),
                new StreamChunk(content: ' 🎉'),
            ]);

            $response = new StreamedChatResponse($stream);
            $text = $response->getText();

            expect($text)->toBe('日本語 🎉');
        });

        it('can be called multiple times after consumption', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);

            $text1 = $response->getText();
            $text2 = $response->getText();

            expect($text1)->toBe($text2);
            expect($text1)->toBe('test');
        });
    });

    describe('getMetadata()', function () {
        it('returns ChatResponseMetadata instance', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata)->toBeInstanceOf(ChatResponseMetadata::class);
        });

        it('includes accumulated content', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'Hello '),
                new StreamChunk(content: 'World'),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->content)->toBe('Hello World');
        });

        it('includes role from chunks', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant'),
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->role)->toBe('assistant');
        });

        it('includes finish reason', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
                new StreamChunk(finishReason: 'stop'),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->finishReason)->toBe('stop');
        });

        it('includes model name', function () {
            $stream = createMockStream([
                new StreamChunk(model: 'gpt-4'),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->model)->toBe('gpt-4');
        });

        it('includes token counts', function () {
            $stream = createMockStream([
                new StreamChunk(inputTokens: 50, outputTokens: 100),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->inputTokens)->toBe(50);
            expect($metadata->outputTokens)->toBe(100);
        });

        it('calculates total tokens', function () {
            $stream = createMockStream([
                new StreamChunk(inputTokens: 50, outputTokens: 100),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->totalTokens)->toBe(150);
        });

        it('handles null tokens for total', function () {
            $stream = createMockStream([
                new StreamChunk(inputTokens: 50),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->totalTokens)->toBeNull();
        });

        it('includes tool calls', function () {
            $stream = createMockStream([
                new StreamChunk(toolCalls: [['name' => 'search']]),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->toolCalls)->toHaveCount(1);
            expect($metadata->toolCalls[0]['name'])->toBe('search');
        });

        it('consumes stream if not already consumed', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);

            expect($response->isConsumed())->toBeFalse();

            $response->getMetadata();

            expect($response->isConsumed())->toBeTrue();
        });

        it('can be called multiple times', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test', role: 'assistant'),
            ]);

            $response = new StreamedChatResponse($stream);

            $metadata1 = $response->getMetadata();
            $metadata2 = $response->getMetadata();

            expect($metadata1->role)->toBe($metadata2->role);
            expect($metadata1->content)->toBe($metadata2->content);
        });
    });

    describe('toChatResponse()', function () {
        it('returns ChatResponse instance', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);
            $chatResponse = $response->toChatResponse();

            expect($chatResponse)->toBeInstanceOf(ChatResponse::class);
        });

        it('includes content in ChatResponse', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'Hello '),
                new StreamChunk(content: 'World'),
            ]);

            $response = new StreamedChatResponse($stream);
            $chatResponse = $response->toChatResponse();

            expect($chatResponse->content)->toBe('Hello World');
        });

        it('includes role in ChatResponse', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant', content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);
            $chatResponse = $response->toChatResponse();

            expect($chatResponse->role)->toBe('assistant');
        });

        it('includes token counts in ChatResponse', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test', inputTokens: 10, outputTokens: 20),
            ]);

            $response = new StreamedChatResponse($stream);
            $chatResponse = $response->toChatResponse();

            expect($chatResponse->inputTokens)->toBe(10);
            expect($chatResponse->outputTokens)->toBe(20);
        });

        it('includes finish reason in ChatResponse', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test', finishReason: 'stop'),
            ]);

            $response = new StreamedChatResponse($stream);
            $chatResponse = $response->toChatResponse();

            expect($chatResponse->finishReason)->toBe('stop');
        });

        it('includes model in ChatResponse', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test', model: 'gpt-4'),
            ]);

            $response = new StreamedChatResponse($stream);
            $chatResponse = $response->toChatResponse();

            expect($chatResponse->model)->toBe('gpt-4');
        });

        it('includes tool calls in raw data', function () {
            $stream = createMockStream([
                new StreamChunk(toolCalls: [['name' => 'search']]),
            ]);

            $response = new StreamedChatResponse($stream);
            $chatResponse = $response->toChatResponse();

            expect($chatResponse->raw['toolCalls'])->toHaveCount(1);
            expect($chatResponse->raw['toolCalls'][0]['name'])->toBe('search');
        });

        it('consumes stream', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);
            $response->toChatResponse();

            expect($response->isConsumed())->toBeTrue();
        });
    });

    describe('isConsumed()', function () {
        it('returns false initially', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);

            expect($response->isConsumed())->toBeFalse();
        });

        it('returns true after chunks() iteration', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);

            foreach ($response->chunks() as $chunk) {
                // Iterate
            }

            expect($response->isConsumed())->toBeTrue();
        });

        it('returns true after getText()', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);
            $response->getText();

            expect($response->isConsumed())->toBeTrue();
        });

        it('returns true after getMetadata()', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);
            $response->getMetadata();

            expect($response->isConsumed())->toBeTrue();
        });

        it('returns true after toChatResponse()', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
            ]);

            $response = new StreamedChatResponse($stream);
            $response->toChatResponse();

            expect($response->isConsumed())->toBeTrue();
        });
    });

    describe('Real-world Scenarios', function () {
        it('handles typical OpenAI streaming response', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant', content: ''),
                new StreamChunk(content: 'Hello'),
                new StreamChunk(content: ','),
                new StreamChunk(content: ' how'),
                new StreamChunk(content: ' can'),
                new StreamChunk(content: ' I'),
                new StreamChunk(content: ' help'),
                new StreamChunk(content: '?'),
                new StreamChunk(content: '', finishReason: 'stop', inputTokens: 10, outputTokens: 8),
            ]);

            $response = new StreamedChatResponse($stream);

            $accumulated = '';
            foreach ($response->chunks() as $chunk) {
                if ($chunk->hasContent()) {
                    $accumulated .= $chunk->content;
                }
            }

            expect($accumulated)->toBe('Hello, how can I help?');
            expect($response->getMetadata()->finishReason)->toBe('stop');
            expect($response->getMetadata()->totalTokens)->toBe(18);
        });

        it('handles tool call streaming', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant'),
                new StreamChunk(toolCalls: [['name' => 'get_weather', 'arguments' => ['location' => 'Paris']]]),
                new StreamChunk(finishReason: 'tool_calls', inputTokens: 20, outputTokens: 15),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->finishReason)->toBe('tool_calls');
            expect($metadata->toolCalls)->toHaveCount(1);
            expect($metadata->toolCalls[0]['name'])->toBe('get_weather');
        });

        it('handles empty stream', function () {
            $stream = createMockStream([]);

            $response = new StreamedChatResponse($stream);
            $text = $response->getText();

            expect($text)->toBe('');
            expect($response->isConsumed())->toBeTrue();
        });

        it('handles metadata-only chunks', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant', model: 'gpt-4'),
                new StreamChunk(inputTokens: 5),
                new StreamChunk(outputTokens: 10),
                new StreamChunk(finishReason: 'length'),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->content)->toBe('');
            expect($metadata->role)->toBe('assistant');
            expect($metadata->model)->toBe('gpt-4');
            expect($metadata->totalTokens)->toBe(15);
        });

        it('handles progressive content building', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'The'),
                new StreamChunk(content: ' quick'),
                new StreamChunk(content: ' brown'),
                new StreamChunk(content: ' fox'),
                new StreamChunk(content: ' jumps'),
            ]);

            $response = new StreamedChatResponse($stream);

            $parts = [];
            foreach ($response->chunks() as $chunk) {
                $parts[] = $chunk->content;
            }

            expect($parts)->toBe(['The', ' quick', ' brown', ' fox', ' jumps']);
            expect($response->getText())->toBe('The quick brown fox jumps');
        });
    });

    describe('Edge Cases', function () {
        it('handles chunks with null values', function () {
            $stream = createMockStream([
                new StreamChunk(content: null, role: null, finishReason: null),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->content)->toBe('');
            expect($metadata->role)->toBeNull();
            expect($metadata->finishReason)->toBeNull();
        });

        it('handles metadata updates in last chunk', function () {
            $stream = createMockStream([
                new StreamChunk(content: 'test'),
                new StreamChunk(
                    role: 'assistant',
                    finishReason: 'stop',
                    model: 'gpt-4',
                    inputTokens: 5,
                    outputTokens: 10,
                ),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            expect($metadata->role)->toBe('assistant');
            expect($metadata->finishReason)->toBe('stop');
            expect($metadata->model)->toBe('gpt-4');
        });

        it('handles very long content', function () {
            $chunks = [];
            for ($i = 0; $i < 1000; $i++) {
                $chunks[] = new StreamChunk(content: 'word ');
            }

            $stream = createMockStream($chunks);
            $response = new StreamedChatResponse($stream);

            $text = $response->getText();

            expect(strlen($text))->toBe(5000); // 1000 * 5 characters
        });

        it('preserves metadata across null updates', function () {
            $stream = createMockStream([
                new StreamChunk(role: 'assistant', model: 'gpt-4'),
                new StreamChunk(content: 'test', role: null, model: null),
                new StreamChunk(finishReason: 'stop'),
            ]);

            $response = new StreamedChatResponse($stream);
            $metadata = $response->getMetadata();

            // Should keep first role and model
            expect($metadata->role)->toBe('assistant');
            expect($metadata->model)->toBe('gpt-4');
            expect($metadata->finishReason)->toBe('stop');
        });
    });
});
