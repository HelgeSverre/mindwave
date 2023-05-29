<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\Prompts\PromptTemplate;

interface LLM
{
    // TODO(29 May 2023) ~ Helge: These methods names are vague, rename them to something better.

    public function predict(string $prompt): ?string;

    public function run(PromptTemplate $promptTemplate): mixed;
}
