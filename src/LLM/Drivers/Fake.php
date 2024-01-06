<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;

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
}
