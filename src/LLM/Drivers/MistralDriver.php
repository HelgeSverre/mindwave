<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use HelgeSverre\Mistral\Mistral;
use Mindwave\Mindwave\Contracts\LLM;

class MistralDriver extends BaseDriver implements LLM
{
    public function __construct(
        protected Mistral $client,
        protected string $model = 'mistral-medium',
        protected ?string $systemMessage = null,
        protected int $maxTokens = 800,
        protected float $temperature = 0.7,
        protected bool $safeMode = false,
        protected ?int $randomSeed = null
    ) {

    }

    public function generateText(string $prompt): ?string
    {
        $response = $this->client->simpleChat()->create(
            messages: [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            model: $this->model,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            safeMode: $this->safeMode,
            randomSeed: $this->randomSeed
        );

        return $response->content;
    }
}
