<?php

namespace Mindwave\Mindwave\Prompts\OutputParsers;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Mindwave\Mindwave\Contracts\OutputParser;

class JsonOutputParser implements OutputParser
{
    public function getFormatInstructions(): string
    {
        return <<<'TEXT'
RESPONSE FORMAT INSTRUCTIONS
----------------------------
When responding to me please, please output the response in the following format:
```json
{
    // response
}
```
However, above all else, all responses must adhere to the format of RESPONSE FORMAT INSTRUCTIONS.
Remember to respond with a JSON blob, and NOTHING else.
TEXT;
    }

    public function parse(string $response): array
    {
        $cleaned = Str::of($response)->between('```json', '```')->trim();

        if (! $cleaned->isJson()) {
            throw new InvalidArgumentException('Could not parse response');
        }

        return json_decode($cleaned, true);

    }
}
