<?php

namespace Mindwave\Mindwave\LLM;

use Illuminate\Support\Manager;
use Mindwave\Mindwave\LLM\Drivers\Fake;
use Mindwave\Mindwave\LLM\Drivers\OpenAIChat;
use Mindwave\Mindwave\LLM\Drivers\OpenAICompletion;
use OpenAI;

class LLMManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('mindwave-llm.default');
    }

    public function createFakeDriver()
    {
        return new Fake();
    }

    public function createOpenAIChatDriver()
    {
        return new OpenAIChat(
            client: OpenAI::client(
                apiKey: $this->config->get('mindwave-llm.llms.openai_chat.api_key'),
                organization: $this->config->get('mindwave-llm.llms.openai_chat.org_id')
            ),
            model: $this->config->get('mindwave-llm.llms.openai_chat.model'),
            maxTokens: $this->config->get('mindwave-llm.llms.openai_chat.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.openai_chat.temperature'),
        );
    }

    public function createOpenAICompletionDriver()
    {
        return new OpenAICompletion(
            client: OpenAI::client(
                apiKey: $this->config->get('mindwave-llm.llms.openai_completion.api_key'),
                organization: $this->config->get('mindwave-llm.llms.openai_completion.org_id')
            ),
            model: $this->config->get('mindwave-llm.llms.openai_completion.model'),
            maxTokens: $this->config->get('mindwave-llm.llms.openai_completion.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.openai_completion.temperature'),
        );
    }
}
