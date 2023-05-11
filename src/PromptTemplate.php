<?php

namespace App\Robot;

use Illuminate\Support\Str;

class PromptTemplate
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

    public static function from(string $path): PromptTemplate
    {
        return new self(
            file_get_contents($path)
        );
    }

    public function format(array $replacements = []): string
    {
        return collect($replacements)->reduce(
            callback: fn ($final, $with, $replace) => $final->replace($replace, $with),
            initial: Str::of($this->content)
        )->toString();
    }
}
