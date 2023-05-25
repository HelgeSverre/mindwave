<?php

namespace Mindwave\Mindwave\Prompts\OutputParsers;

use Mindwave\Mindwave\Contracts\OutputParser;

class TextOutputParser implements OutputParser
{
    public function getFormatInstructions(): string
    {
        return '';
    }

    public function parse(string $response): string
    {
        return $response;
    }
}
