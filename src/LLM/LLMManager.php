<?php

namespace Mindwave\Mindwave\LLM;

use HelgeSverre\Mistral\Mistral;
use Illuminate\Support\Manager;
use Mindwave\Mindwave\LLM\Drivers\Fake;
use Mindwave\Mindwave\LLM\Drivers\MistralDriver;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\OpenAI as OpenAIDriver;
use OpenAI;

class LLMManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('mindwave-llm.default');
    }

    public function createFakeDriver(): Fake
    {
        return new Fake();
    }

    public function createOpenAIDriver(): OpenAIDriver
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

    public function createMistralDriver(): MistralDriver
    {
        return new MistralDriver(
            client: new Mistral(
                apiKey: $this->config->get('mindwave-llm.llms.mistral.api_key'),
                baseUrl: $this->config->get('mindwave-llm.llms.mistral.base_url'),
            ),
            model: $this->config->get('mindwave-llm.llms.openai.model'),
            systemMessage: $this->config->get('mindwave-llm.llms.mistral.system_message'),
            maxTokens: $this->config->get('mindwave-llm.llms.openai.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.openai.temperature'),
            safeMode: $this->config->get('mindwave-llm.llms.mistral.safe_mode'),
            randomSeed: $this->config->get('mindwave-llm.llms.mistral.random_seed'),
        );
    }
}
