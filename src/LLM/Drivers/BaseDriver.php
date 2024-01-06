<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\Drivers\Concerns\HasOptions;
use Mindwave\Mindwave\LLM\Drivers\Concerns\HasSystemMessage;
use Mindwave\Mindwave\Prompts\PromptTemplate;

abstract class BaseDriver implements LLM
{
    use HasOptions;
    use HasSystemMessage;

    public function generate(PromptTemplate $promptTemplate, array $inputs = []): mixed
    {
        $formatted = $promptTemplate->format($inputs);

        $response = $this->generateText($formatted);

        return $promptTemplate->parse($response);
    }
}
