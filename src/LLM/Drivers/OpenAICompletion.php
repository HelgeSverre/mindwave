<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;
use OpenAI\Client;

class OpenAICompletion implements LLM
{
    protected Client $client;

    protected string $model;

    protected int $maxTokens;

    protected float $temperature;

    public function __construct(
        Client $client,
        string $model = 'text-davinci-003',
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
        $response = $this->client->completions()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model,
            'prompt' => $prompt,
        ]);

        return $response->choices[0]?->text;
    }
}
