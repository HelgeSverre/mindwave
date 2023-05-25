<?php

namespace Mindwave\Mindwave\Prompts;

use Illuminate\Support\Str;

class OldPromptTemplate
{
    public string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public static function combine($items): string
    {
        return implode("\n", array_filter($items));
    }

    public static function from(string $path): OldPromptTemplate
    {
        return new self(
            file_get_contents($path)
        );
    }

    public function format(array $replacements = []): string
    {
        return Str::swap($replacements, $this->content);
    }
}
