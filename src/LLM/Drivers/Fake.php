<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;

class Fake implements LLM
{
    protected string $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    public function predict(string $prompt): ?string
    {
        return $this->response;
    }
}
