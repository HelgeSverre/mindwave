<?php

namespace Mindwave\Mindwave\Message;

class AIMessage extends BaseMessage
{
    public function formatChatML(): string
    {
        return "<|im_start|>assistant\n" . $this->content . "\n<|im_end|>";
    }

    public function type(): string
    {
        return 'ai';
    }
}
