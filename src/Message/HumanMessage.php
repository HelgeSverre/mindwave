<?php

namespace Mindwave\Mindwave\Message;

class HumanMessage extends BaseMessage
{
    public function formatChatML(): string
    {
        return "<|im_start|>user\n".$this->content."\n<|im_end|>";
    }

    public function getType(): string
    {
        return 'human';
    }
}
