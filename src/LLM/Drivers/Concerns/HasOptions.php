<?php

namespace Mindwave\Mindwave\LLM\Drivers\Concerns;

trait HasOptions
{
    protected array $options = [];

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

}
