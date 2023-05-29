<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Prompts\PromptTemplate;

class Fake implements LLM
{
    public function predict(string $prompt): ?string
    {
        return $prompt;
    }

    public function run(PromptTemplate $promptTemplate): mixed
    {
        return 'implement this';
    }
}
