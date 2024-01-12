<?php

namespace Mindwave\Mindwave\Crew;

use Exception;

class Task
{
    protected string $output;

    public function __construct(
        public string $description,
        public ?Agent $agent = null,
        public array $tools = [],

    ) {
    }

    public function execute(?string $context = null): string
    {

        if ($this->agent == null) {
            throw new Exception("The task '{$this->description}' has no agent assigned to it.");
        }

        $result = $this->agent->executeTask(
            task: $this->description,
            context: $context,
            tools: $this->tools,
        );

        $this->output = $result;

        return $result;
    }

}
