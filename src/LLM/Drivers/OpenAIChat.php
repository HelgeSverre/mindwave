<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Mindwave\Mindwave\Contracts\LLM;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponseMessage;

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
                // TODO(16 May 2023) ~ Helge:
                //  When using chat endpoint should we provide the history as
                //  separate messages does it matter, in the end does it get concatenated
                //  together behind the scenes or does the role provide any context ot the model? Investigate.
                ['role' => 'system', 'content' => $prompt],
            ],
        ]);

        /** @var CreateResponseMessage $message */
        $message = $response->choices[0]->message;

        return $message->content;
    }
}
