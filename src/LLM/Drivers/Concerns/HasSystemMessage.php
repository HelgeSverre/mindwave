<?php

namespace Mindwave\Mindwave\LLM\Drivers\Concerns;

trait HasSystemMessage
{
    protected ?string $systemMessage = null;

    public function setSystemMessage(string $systemMessage): static
    {
        $this->systemMessage = $systemMessage;

        return $this;
    }
}
