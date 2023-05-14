<?php

namespace Mindwave\Mindwave\Knowledge;

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
}