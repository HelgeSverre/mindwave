<?php

namespace Mindwave\Mindwave\LLM;

use HelgeSverre\Mistral\Mistral;
use Illuminate\Support\Manager;
use Mindwave\Mindwave\LLM\Drivers\Anthropic\AnthropicDriver;
use Mindwave\Mindwave\LLM\Drivers\Fake;
use Mindwave\Mindwave\LLM\Drivers\GeminiDriver;
use Mindwave\Mindwave\LLM\Drivers\MistralDriver;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\OpenAI as OpenAIDriver;
use OpenAI;
use OpenAI\Contracts\ClientContract;

class LLMManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('mindwave-llm.default');
    }

    public function createFakeDriver(): Fake
    {
        return new Fake;
    }

    public function createOpenAIDriver(?ClientContract $client = null): OpenAIDriver
    {
        $client = $client ?? OpenAI::client(
            apiKey: $this->config->get('mindwave-llm.llms.openai.api_key'),
            organization: $this->config->get('mindwave-llm.llms.openai.org_id')
        );

        return new OpenAIDriver(
            client: $client,
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
            model: $this->config->get('mindwave-llm.llms.mistral.model'),
            systemMessage: $this->config->get('mindwave-llm.llms.mistral.system_message'),
            maxTokens: $this->config->get('mindwave-llm.llms.mistral.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.mistral.temperature'),
            safeMode: $this->config->get('mindwave-llm.llms.mistral.safe_mode'),
            randomSeed: $this->config->get('mindwave-llm.llms.mistral.random_seed'),
        );
    }

    public function createAnthropicDriver(): AnthropicDriver
    {
        $client = \Anthropic::client(
            apiKey: $this->config->get('mindwave-llm.llms.anthropic.api_key')
        );

        return new AnthropicDriver(
            client: $client,
            model: $this->config->get('mindwave-llm.llms.anthropic.model'),
            systemMessage: $this->config->get('mindwave-llm.llms.anthropic.system_message'),
            maxTokens: $this->config->get('mindwave-llm.llms.anthropic.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.anthropic.temperature'),
        );
    }

    public function createGroqDriver(): OpenAIDriver
    {
        $client = OpenAI::factory()
            ->withApiKey($this->config->get('mindwave-llm.llms.groq.api_key'))
            ->withBaseUri('https://api.groq.com/openai/v1')
            ->make();

        return new OpenAIDriver(
            client: $client,
            model: $this->config->get('mindwave-llm.llms.groq.model'),
            maxTokens: $this->config->get('mindwave-llm.llms.groq.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.groq.temperature'),
        );
    }

    public function createXaiDriver(): OpenAIDriver
    {
        $client = OpenAI::factory()
            ->withApiKey($this->config->get('mindwave-llm.llms.xai.api_key'))
            ->withBaseUri('https://api.x.ai/v1')
            ->make();

        return new OpenAIDriver(
            client: $client,
            model: $this->config->get('mindwave-llm.llms.xai.model'),
            maxTokens: $this->config->get('mindwave-llm.llms.xai.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.xai.temperature'),
        );
    }

    public function createMoonshotDriver(): OpenAIDriver
    {
        $client = OpenAI::factory()
            ->withApiKey($this->config->get('mindwave-llm.llms.moonshot.api_key'))
            ->withBaseUri('https://api.moonshot.ai/v1')
            ->make();

        return new OpenAIDriver(
            client: $client,
            model: $this->config->get('mindwave-llm.llms.moonshot.model'),
            maxTokens: $this->config->get('mindwave-llm.llms.moonshot.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.moonshot.temperature'),
        );
    }

    public function createGeminiDriver(): GeminiDriver
    {
        return new GeminiDriver(
            apiKey: $this->config->get('mindwave-llm.llms.gemini.api_key'),
            model: $this->config->get('mindwave-llm.llms.gemini.model'),
            maxTokens: $this->config->get('mindwave-llm.llms.gemini.max_tokens'),
            temperature: $this->config->get('mindwave-llm.llms.gemini.temperature'),
        );
    }
}
