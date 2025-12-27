<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Generator;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionBuilder;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionCall;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;

class Fake extends BaseDriver implements LLM
{
    protected string $model = 'fake-model';

    protected string $response = '';

    protected int $streamChunkSize = 5;

    public function respondsWith(string $response): self
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Set the chunk size for streaming responses.
     *
     * @param  int  $size  Number of characters per chunk
     */
    public function streamChunkSize(int $size): self
    {
        $this->streamChunkSize = $size;

        return $this;
    }

    public function generateText(string $prompt): ?string
    {
        return $this->response;
    }

    /**
     * Simulate streaming by yielding the response in chunks.
     *
     * @param  string  $prompt  The prompt (ignored in fake implementation)
     * @return Generator<string> Yields text chunks
     */
    public function streamText(string $prompt): Generator
    {
        $response = $this->response;
        $length = mb_strlen($response);

        for ($i = 0; $i < $length; $i += $this->streamChunkSize) {
            yield mb_substr($response, $i, $this->streamChunkSize);
        }
    }

    /**
     * Simulate streaming chat with structured chunks.
     *
     * @param  array<array{role: string, content: string}>  $messages  Array of messages
     * @param  array  $options  Additional options
     * @return Generator<StreamChunk> Yields structured chunks
     */
    public function streamChat(array $messages, array $options = []): Generator
    {
        $response = $this->response;
        $length = mb_strlen($response);
        $isFirst = true;

        for ($i = 0; $i < $length; $i += $this->streamChunkSize) {
            $content = mb_substr($response, $i, $this->streamChunkSize);
            $isLast = ($i + $this->streamChunkSize) >= $length;

            yield new StreamChunk(
                content: $content,
                role: $isFirst ? 'assistant' : null,
                finishReason: $isLast ? 'stop' : null,
                model: $this->model,
                inputTokens: $isFirst ? 10 : null,
                outputTokens: $isLast ? 10 : null,
                raw: [
                    'index' => $i,
                    'chunkSize' => $this->streamChunkSize,
                    'isFirst' => $isFirst,
                    'isLast' => $isLast,
                ],
            );

            $isFirst = false;
        }
    }

    public function functionCall(string $prompt, array|FunctionBuilder $functions, ?string $requiredFunction = 'auto'): FunctionCall|string|null
    {
        return new FunctionCall(
            name: 'fake_function',
            arguments: ['example function call response'],
            rawArguments: json_encode(['example function call response']),
        );
    }

    public function chat(array $messages, array $options = []): \Mindwave\Mindwave\LLM\Responses\ChatResponse
    {
        return new \Mindwave\Mindwave\LLM\Responses\ChatResponse(
            content: $this->response,
            role: 'assistant',
            inputTokens: 10,
            outputTokens: 10,
            finishReason: 'stop',
            model: 'fake-model',
            raw: [],
        );
    }
}
