<?php

namespace Mindwave\Mindwave\Memory;

use Mindwave\Mindwave\Contracts\Message;
use Mindwave\Mindwave\Message\AIMessage;
use Mindwave\Mindwave\Message\HumanMessage;

abstract class BaseChatMessageHistory
{
    /**
     * @var Message[]
     */
    protected array $messages = [];

    public function make(): self
    {
        return new static();
    }

    abstract public static function fromMessages(array $messages): self;

    abstract public function addAiMessage($message): void;

    abstract public function addUserMessage($message): void;

    abstract public function clear(): void;

    abstract public function toArray(): array;

    public function conversationAsString(
        string $humanPrefix = 'Human',
        string $aiPrefix = 'AI'
    ): string {
        $stringMessages = [];

        foreach ($this->messages as $m) {

            $role = match (true) {
                $m instanceof HumanMessage => $humanPrefix,
                $m instanceof AIMessage => $aiPrefix,
                default => 'Unknown',
            };

            $stringMessages[] = sprintf('%s: %s', $role, $m->content());
        }

        return implode("\n", $stringMessages);
    }
}
