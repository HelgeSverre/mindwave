<?php

namespace Mindwave\Mindwave\LLM;

class Fake implements LLM
{
    protected string $response;

    public function __construct($response)
    {

        $this->response = $response;
    }

    public function predict($input): string
    {
        return $this->response;
    }
}
