<?php

namespace Mindwave\Mindwave\LLM;

use Illuminate\Support\Manager;
use Mindwave\Mindwave\LLM\Drivers\Fake;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\OpenAI as OpenAIDriver;
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

    public function createOpenAIDriver()
    {
        return new OpenAIDriver(
            client: OpenAI::client(
                apiKey: $this->config->get('mindwave-llm.llms.openai.api_key'),
                organization: $this->config->get('mindwave-llm.llms.openai.org_id')
            ),
            model: $this->config->get('mindwave-llm.llms.openai.model'),
            maxTokens: $this->config->get('mindwave-llm.llms.openai.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.openai.temperature'),
        );
    }
}
