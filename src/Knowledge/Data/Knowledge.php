<?php

namespace Mindwave\Mindwave\Knowledge\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;

class Knowledge
{
    use Macroable;

    protected string $content;

    protected array $meta = [];

    public function __construct(string $content, array $meta = [])
    {
        $this->content = $content;
        $this->meta = $meta;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public static function make(string $content, array $meta = []): self
    {
        return new self($content, $meta);
    }

    public function getMetaValue(string $key, $fallback = null): mixed
    {
        return Arr::get($this->meta, $key, $fallback);
    }

    public function toArray(): array
    {
        return [
            '_value' => $this->content,
            ...$this->meta,
        ];
    }
}
