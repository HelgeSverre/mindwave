<?php

namespace Mindwave\Mindwave\LLM;

use Mindwave\Mindwave\Contracts\LLM;
use OpenAI\Client;

class OpenAIChat implements LLM
{
    protected Client $client;

    protected string $model;

    protected int $maxTokens;

    protected float $temperature;

    public function __construct(
        Client $client,
        string $model = 'gpt-3.5-turbo',
        int $maxTokens = 800,
        float $temperature = 0.7,
    ) {
        $this->client = $client;
        $this->model = $model;
        $this->maxTokens = $maxTokens;
        $this->temperature = $temperature;
    }

    public function predict(string $prompt): ?string
    {
        $response = $this->client->chat()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
            ],
        ]);

        return $response->choices[0]?->message;
    }
}
