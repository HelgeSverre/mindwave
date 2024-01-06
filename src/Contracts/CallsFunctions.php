<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionBuilder;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionCall;

interface CallsFunctions
{
    public function functionCall(
        string $prompt,
        FunctionBuilder $functions,
    ): ?FunctionCall;

}
