<?php

namespace Mindwave\Mindwave\Tools;

use Illuminate\Support\Collection;
use Mindwave\Mindwave\Contracts\Toolkit as Contract;

class Toolkit implements Contract
{
    protected array $tools;

    public function __construct(array|Collection $tools)
    {
        $this->tools = $tools instanceof Collection ? $tools->all() : $tools;
    }

    public function fromTools(array|Collection $tools): self
    {
        return new self($tools);
    }

    public function tools(): array
    {
        return $this->tools;
    }
}
