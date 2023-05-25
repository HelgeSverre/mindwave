<?php

namespace Mindwave\Mindwave\Prompts\OutputParsers;

class CommaSeparatedListOutputParser extends JsonOutputParser
{
    public function getFormatInstructions(): string
    {
        return 'Your response should be a list of comma separated values, eg: `foo, bar, baz`';
    }

    public function parse(string $text): array
    {
        return array_map('trim', explode(',', $text));
    }
}
