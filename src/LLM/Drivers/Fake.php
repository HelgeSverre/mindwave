<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionBuilder;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionCall;

class Fake extends BaseDriver implements LLM
{
    protected string $response = '';

    public function respondsWith(string $response)
    {
        $this->response = $response;
    }

    public function generateText(string $prompt): ?string
    {
        return $this->response;
    }

    public function functionCall(string $prompt, array|FunctionBuilder $functions, ?string $requiredFunction = 'auto'): FunctionCall|string|null
    {
        return new FunctionCall(
            name: 'fake_function',
            arguments: ['example function call response'],
            rawArguments: json_encode(['example function call response']),
        );
    }
}
