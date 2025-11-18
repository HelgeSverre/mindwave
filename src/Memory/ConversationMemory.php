<?php

namespace Mindwave\Mindwave\Memory;

use Mindwave\Mindwave\Contracts\Memory;
use Mindwave\Mindwave\Contracts\Message;
use Mindwave\Mindwave\Message\ChatMessage;
use Mindwave\Mindwave\Message\Role;

class ConversationMemory implements Memory
{
    /**
     * @param  Message[]  $messages
     */
    public function __construct(protected array $messages = []) {}

    public static function make(): self
    {
        return new static;
    }

    public function conversationAsString(
        string $humanPrefix = 'Human',
        string $aiPrefix = 'AI',
        string $systemPrefix = 'System',
    ): string {
        $messages = [];

        foreach ($this->messages as $m) {

            $role = match ($m->role()) {
                Role::user->value => $humanPrefix,
                Role::ai->value => $aiPrefix,
                Role::system->value => $systemPrefix,
                default => 'Unknown',
            };

            $messages[] = sprintf('%s: %s', $role, $m->content());
        }

        return implode("\n", $messages);
    }

    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public static function fromMessages(array $messages): self
    {
        $instance = self::make();

        foreach ($messages as $message) {

            if ($message['role'] == Role::system->value) {
                $instance->addSystemMessage($message['content'], $message['meta'] ?? null);
            }

            if ($message['role'] == Role::ai->value) {
                $instance->addAiMessage($message['content'], $message['meta'] ?? null);
            }

            if ($message['role'] == Role::user->value) {
                $instance->addUserMessage($message['content'], $message['meta'] ?? null);
            }
        }

        return $instance;
    }

    public function addSystemMessage($message, ?array $meta = []): void
    {
        $this->messages[] = ChatMessage::makeSystemMessage($message, $meta);
    }

    public function addAiMessage($message, ?array $meta = []): void
    {
        $this->messages[] = ChatMessage::makeAiMessage($message, $meta);
    }

    public function addUserMessage($message, ?array $meta = []): void
    {
        $this->messages[] = ChatMessage::makeUserMessage($message, $meta);
    }

    public function clear(): void
    {
        $this->messages = [];
    }

    public function toArray(): array
    {
        return array_map(
            fn (Message $message) => $message->toArray(),
            $this->messages
        );
    }
}
