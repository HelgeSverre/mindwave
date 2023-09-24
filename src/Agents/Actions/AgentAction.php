<?php

namespace Mindwave\Mindwave\Agents\Actions;

class AgentAction
{
    public string $tool;

    public ?string $toolInput;

    public function __construct(string $tool, string $toolInput = null)
    {
        $this->tool = $tool;
        $this->toolInput = $toolInput;
    }
}
