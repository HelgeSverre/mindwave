<?php

namespace Mindwave\Mindwave\Contracts;

interface Memory
{
    public function conversationAsString(
        string $humanPrefix = 'Human',
        string $aiPrefix = 'AI',
        string $systemPrefix = 'System'
    ): string;

    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public static function fromMessages(array $messages): Memory;

    public function addSystemMessage($message, ?array $meta = []): void;

    public function addAiMessage($message, ?array $meta = []): void;

    public function addUserMessage($message, ?array $meta = []): void;

    public function clear(): void;

    public function toArray(): array;
}
