<?php

namespace Mindwave\Mindwave\LLM\Functions;

readonly class FunctionCall
{
    public function __construct(
        public string $name,
        public array $arguments,
        public string $rawArguments
    ) {

    }
}
