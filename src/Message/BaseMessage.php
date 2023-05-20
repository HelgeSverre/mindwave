<?php

namespace Mindwave\Mindwave\Message;

use Mindwave\Mindwave\Contracts\Message;

abstract class BaseMessage implements Message
{
    protected string $content;

    protected array $data;

    public function __construct(string $content, array $data = [])
    {
        $this->content = $content;
        $this->data = $data;
    }

    // TODO(11 May 2023) ~ Helge: remove
    abstract public function formatChatML(): string;

    abstract public function type(): string;

    public function content(): string
    {
        return $this->content;
    }

    public function toArray(): array
    {
        return [
            // TODO(20 mai 2023) ~ Helge: revisit if this should be "role" instead
            'type' => $this->type(),
            'content' => $this->content(),
            'chatml' => $this->formatChatML(),
        ];
    }
}
