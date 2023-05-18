<?php

namespace Mindwave\Mindwave\Memory;

use Mindwave\Mindwave\Message\AIMessage;
use Mindwave\Mindwave\Message\BaseMessage;
use Mindwave\Mindwave\Message\HumanMessage;
use Mindwave\Mindwave\Message\SystemMessage;

class ChatMessageHistory
{
    /**
     * @var BaseMessage[]
     */
    protected array $messages = [];

    public static function fromMessages(array $messages): self
    {
        $instance = new self();

        foreach ($messages as $message) {
            if ($message['role'] == 'system') {
                $instance->addSystemMessage($message['content']);
            }

            if ($message['role'] == 'assistant') {
                $instance->addAiMessage($message['content']);
            }

            if ($message['role'] == 'user') {
                $instance->addUserMessage($message['content']);
            }
        }

        return $instance;
    }

    public function addSystemMessage($message): void
    {
        $this->messages[] = new SystemMessage($message);
    }

    public function addAiMessage($message): void
    {
        $this->messages[] = new AIMessage($message);
    }

    public function addUserMessage($message): void
    {
        $this->messages[] = new HumanMessage($message);
    }

    public function clear(): void
    {
        $this->messages = [];
    }

    public function toArray(): array
    {
        return $this->messages;
    }

    public function conversationAsString(
        string $humanPrefix = 'Human',
        string $aiPrefix = 'AI'
    ): string {
        $stringMessages = [];
        foreach ($this->messages as $m) {
            if ($m instanceof HumanMessage) {
                $role = $humanPrefix;
            } elseif ($m instanceof AIMessage) {
                $role = $aiPrefix;
            } elseif ($m instanceof SystemMessage) {
                $role = $aiPrefix;
            } else {
                $role = 'Unknown';
            }

            $stringMessages[] = sprintf('%s: %s', $role, $m->getContent());
        }

        return implode("\n", $stringMessages);
    }
}
