<?php

namespace Mindwave\Mindwave\Tools;

use Closure;
use Mindwave\Mindwave\Contracts\Tool;

class SimpleTool implements Tool
{
    protected string $name;

    protected string $description;

    protected Closure $callback;

    public function __construct(string $name, string $description, Closure $callback)
    {
        $this->name = $name;
        $this->description = $description;
        $this->callback = $callback;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function run($input): string
    {
        return $this->callback->call($this, $input);
    }
}
