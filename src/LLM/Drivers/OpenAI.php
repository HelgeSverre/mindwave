<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\Functions;
use Mindwave\Mindwave\Prompts\PromptTemplate;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use OpenAI\Responses\Completions\CreateResponse as CompletionResponse;

class OpenAI implements LLM
{
    // Completion
    const TEXT_DAVINCI_003 = 'text-davinci-003';

    const TURBO_INSTRUCT = 'gpt-3.5-turbo-instruct';

    // Chat
    const TURBO = 'gpt-3.5-turbo';

    const TURBO_16K = 'gpt-3.5-turbo-16k';

    const GPT4 = 'gpt-4';

    const GPT4_32K = 'gpt-4-32k';

    public function __construct(
        protected Client $client,
        protected string $model = 'gpt-3.5-turbo-16k',
        protected int    $maxTokens = 800,
        protected float  $temperature = 0.7,
    )
    {
    }

    public function model(int $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function run(PromptTemplate $promptTemplate, array $inputs = []): mixed
    {
        $formatted = $promptTemplate->format($inputs);

        $response = $this->predict($formatted);

        return $promptTemplate->parse($response);
    }

    public function functionCall(string $prompt, array|Functions $functions): mixed
    {

        $response = $this->client->chat()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
            ],
            'functions' => $functions instanceof Functions ? $functions->toArray() : $functions,
            '_functions' => [
                [
                    'name' => 'get_current_weather',
                    'description' => 'Get the current weather in a given location',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'location' => [
                                'type' => 'string',
                                'description' => 'The city and state, e.g. San Francisco, CA',
                            ],
                            'unit' => [
                                'type' => 'string',
                                'enum' => ['celsius', 'fahrenheit']
                            ],
                        ],
                        'required' => ['location'],
                    ],
                ]
            ]
        ]);

        //    $result->message->functionCall->name; // 'get_current_weather'
        //    $result->message->functionCall->arguments; // "{\n  \"location\": \"Boston, MA\"\n}"
        //    $result->finishReason; // 'function_call'

        return $promptTemplate->parse($response);
    }

    public function predict(string $prompt): ?string
    {
        $isCompletion = in_array($this->model, [self::TEXT_DAVINCI_003, self::TURBO_INSTRUCT]);

        $response = $isCompletion ? $this->completion($prompt) : $this->chat($prompt);

        return $this->extractResponseText($response);
    }

    protected function extractResponseText(ChatResponse|CompletionResponse $response): string
    {
        return $response instanceof ChatResponse
            ? $response->choices[0]->message->content
            : $response->choices[0]->text;
    }

    public function chat($prompt): ChatResponse
    {
        return $this->client->chat()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
            ],
        ]);
    }

    public function completion($prompt): CompletionResponse
    {
        return $this->client->completions()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'prompt' => $prompt,
        ]);
    }
}
