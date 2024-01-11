<?php

namespace Mindwave\Mindwave\Tools;

use Mindwave\Mindwave\Contracts\Tool;

/**
 * Simple tool that writes to a file at a predefined path
 */
class ReadFile implements Tool
{
    public function name(): string
    {
        return 'File reader';
    }

    public function description(): string
    {
        return 'Reads a file at the given path and returns the contents';
    }

    public function run($input): string
    {

        if (! file_exists($input)) {
            return "There is no file at the given path {$input}";
        }

        $content = file_get_contents($input);

        return "File contents: $content";

    }
}
