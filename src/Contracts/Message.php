<?php

namespace Mindwave\Mindwave\Contracts;

interface Message
{
    public function formatChatML(): string;

    public function type(): string;

    public function content(): string;

    public function toArray(): array;
}
