<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;

class Fake implements LLM
{
    public function predict(string $prompt): ?string
    {
        return $prompt;
    }
}
