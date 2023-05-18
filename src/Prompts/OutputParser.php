<?php

namespace Mindwave\Mindwave\Prompts;

use Exception;
use Illuminate\Support\Str;

class OutputParser
{
    public function parseJson($text): array
    {
        $cleaned = Str::of($text)->between('```json', '```')->trim();

        if ($cleaned->isJson()) {
            return json_decode($cleaned, true);
        }

        throw new Exception("Could not parse response: $text");
    }
}
