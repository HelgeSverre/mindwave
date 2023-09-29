<?php

namespace Mindwave\Mindwave\LLM\Drivers\OpenAI;

namespace Mindwave\Mindwave\LLM\Drivers\OpenAI;

enum Model: string
{
    // Completion
    case textDavinci003 = 'text-davinci-003';
    case turboInstruct = 'gpt-3.5-turbo-instruct';

    // Chat
    case turbo16k = 'gpt-3.5-turbo-16k';
    case turbo = 'gpt-3.5-turbo';
    case gpt4 = 'gpt-4';
    case gpt432k = 'gpt-4-32';

    public function isCompletionModel(): bool
    {
        return match ($this) {
            self::textDavinci003, self::turboInstruct => true,
            default => false,
        };
    }

    public function isChatModel(): bool
    {
        return ! $this->isCompletionModel();
    }
}
