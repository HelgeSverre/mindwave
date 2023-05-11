<?php

namespace Mindwave\Mindwave\Message;

abstract class BaseMessage
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

    abstract public function getType(): string;

    public function getContent(): string
    {
        return $this->content;
    }
}
