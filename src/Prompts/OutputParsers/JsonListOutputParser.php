<?php

namespace Mindwave\Mindwave\Prompts\OutputParsers;

use Illuminate\Support\Arr;

class JsonListOutputParser extends JsonOutputParser
{
    public function getFormatInstructions(): string
    {
        return <<<'TEXT'
RESPONSE FORMAT INSTRUCTIONS
----------------------------
When responding to me please, please output the response in the following format:
```json
{
    "data": array // An array of strings.
}
```
However, above all else, all responses must adhere to the format of RESPONSE FORMAT INSTRUCTIONS.
Remember to respond with a JSON blob with a single key, and NOTHING else.
TEXT;
    }

    public function parse(string $response): array
    {
        return Arr::get(parent::parse($response), 'data', []);
    }
}
