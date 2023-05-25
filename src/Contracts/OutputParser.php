<?php

namespace Mindwave\Mindwave\Contracts;

interface OutputParser
{
    /**
     * Get the format instructions for how the output should be structured.
     */
    public function getFormatInstructions(): string;

    /**
     * Parse the language model response and return the structured result.
     */
    public function parse(string $response): mixed;
}
