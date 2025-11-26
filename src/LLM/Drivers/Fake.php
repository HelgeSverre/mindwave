<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Generator;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionBuilder;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionCall;

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
