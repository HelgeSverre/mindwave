<?php

namespace Mindwave\Mindwave\Memory;

use Mindwave\Mindwave\Contracts\Message;
use Mindwave\Mindwave\Message\AIMessage;
use Mindwave\Mindwave\Message\HumanMessage;

class ConversationBufferMemory extends BaseChatMessageHistory
{
    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public static function fromMessages(array $messages): self
    {
        $instance = new self();

        foreach ($messages as $message) {

            // TODO(20 mai 2023) ~ Helge: Assuming "role" here, however message class defines is as "type"
            if ($message['role'] == 'assistant') {
                $instance->addAiMessage($message['content']);
            }

            if ($message['role'] == 'user') {
                $instance->addUserMessage($message['content']);
            }
        }

        return $instance;
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
        return array_map(
            fn (Message $message) => $message->toArray(),
            $this->messages
        );
    }
}
