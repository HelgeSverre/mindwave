<?php

namespace Mindwave\Mindwave\Document\Data;

use Illuminate\Support\Traits\Macroable;

class Document
{
    use Macroable;

    protected string $content;

    protected array $metadata = [];

    public function __construct(string $content, array $metadata = [])
    {
        $this->content = $content;
        $this->metadata = $metadata;
    }

    public static function make(string $content, array $meta = []): self
    {
        return new self($content, $meta);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function isEmpty(): bool
    {
        return trim($this->content) == '';
    }

    public function isNotEmpty(): bool
    {
        return $this->isEmpty() === false;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }
}
