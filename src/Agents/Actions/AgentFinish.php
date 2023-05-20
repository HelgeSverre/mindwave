<?php

namespace Mindwave\Mindwave\Agents\Actions;

class AgentFinish
{
    public string $response;

    public function __construct(string $response)
    {
        $this->response = $response;
    }
}
