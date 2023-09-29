<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionBuilder;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionCall;
use Mindwave\Mindwave\Prompts\PromptTemplate;

interface LLM
{
    public function setSystemMessage(string $systemMessage);

    public function generateText(string $prompt): ?string;

    public function generate(PromptTemplate $promptTemplate): mixed;

    public function functionCall(string $prompt, array|FunctionBuilder $functions, ?string $requiredFunction = 'auto'): FunctionCall|string|null;
}
