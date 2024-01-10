<?php

namespace Mindwave\Mindwave\LLM\FunctionCalling;

use Closure;
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

    public function add(string $name, Closure $param)
    {
        $this->functions[] = PendingFunction::makeFromClosure($name, $param);

        return $this;
    }

    public function addFunction(string $name, ?string $description = null, ?Closure $closure = null): PendingFunction
    {
        $pendingFunction = new PendingFunction($name, $description);

        if ($closure) {
            $pendingFunction->fromClosure($closure);
        }

        $this->functions[] = $pendingFunction;

        return $pendingFunction;
    }

    public function build(): array
    {
        return array_map(fn (PendingFunction $function) => $function->build(), $this->functions);
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
