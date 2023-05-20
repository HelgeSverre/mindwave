<?php

namespace Mindwave\Mindwave\Contracts;

interface Tool
{
    public function name(): string;

    public function description(): string;

    // TODO(20 mai 2023) ~ Helge: Input parser,
    //  or use reflection to parse a new method that is actually the one running the "tool" with arbitrary inputs,
    //  reflection will parse th tool class, find the arguments and provide a formatted schema the llm has to
    //  use and parse the input into the correct properties and datatypes.

    public function run($input): string;
}
