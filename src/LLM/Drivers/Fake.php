<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionBuilder;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionCall;
use Mindwave\Mindwave\Prompts\PromptTemplate;

class Fake implements LLM
{
    public function generateText(string $prompt): ?string
    {
        // TODO: implement
        return $prompt;
    }

    public function generate(PromptTemplate $promptTemplate): mixed
    {
        // TODO: implement
        return 'implement this';
    }

    public function functionCall(string $prompt, array|FunctionBuilder $functions, ?string $requiredFunction = 'auto'): FunctionCall|string|null
    {
        // TODO: implement
        return 'not implemented';
    }

    public function setSystemMessage(string $systemMessage)
    {
        // TODO: implement
    }
}
