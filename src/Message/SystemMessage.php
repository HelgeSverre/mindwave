<?php

namespace Mindwave\Mindwave\Message;

class SystemMessage extends BaseMessage
{
    public function formatChatML(): string
    {
        return "<|im_start|>system\n".$this->content."\n<|im_end|>";
    }

    public function getType(): string
    {
        return 'system';
    }
}
