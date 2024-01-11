<?php

namespace Mindwave\Mindwave\Tools;

use Mindwave\Mindwave\Contracts\Tool;
use Throwable;

/**
 * Simple tool that writes to a file at a predefined path
 */
class WriteFile implements Tool
{
    public function __construct(protected string $path)
    {
    }

    public function name(): string
    {
        return 'Write text to a text file';
    }

    public function description(): string
    {
        return "Writes text to a text file, the file will be created if it doesn't exist";
    }

    public function run($input): string
    {
        try {

            if (! file_exists($this->path)) {
                touch($this->path);
            }

            file_put_contents($this->path, $input);

            return "Successfully wrote to file {$this->path}";
        } catch (Throwable $th) {
            return "Failed to write to file due to error: {$th->getMessage()}";
        }
    }
}
