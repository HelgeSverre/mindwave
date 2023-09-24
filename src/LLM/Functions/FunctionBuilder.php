<?php

namespace Mindwave\Mindwave\LLM\Functions;

use Illuminate\Contracts\Support\Arrayable;

class FunctionBuilder implements Arrayable
{
    public function __construct(protected array $functions = [])
    {

    }

    public static function make(): self
    {
        return new self();
    }

    public function addFunction(string $name, string $description = null): PendingFunction
    {
        $pendingFunction = new PendingFunction($name, $description);
        $this->functions[] = $pendingFunction;

        return $pendingFunction;
    }

    public function build(): array
    {
        return array_map(function (PendingFunction $function) {
            return $function->build();
        }, $this->functions);
    }

    public function toArray(): array
    {
        return $this->build();
    }

    public function toJson(): string
    {
        return json_encode($this->build(), JSON_PRETTY_PRINT);
    }
}
