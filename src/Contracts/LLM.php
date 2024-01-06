<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\Prompts\PromptTemplate;

interface LLM
{
    public function setSystemMessage(string $systemMessage): static;

    public function setOptions(array $options): static;

    public function generateText(string $prompt): ?string;

    public function generate(PromptTemplate $promptTemplate, array $inputs = []): mixed;
}
