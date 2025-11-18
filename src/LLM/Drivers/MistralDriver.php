<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use HelgeSverre\Mistral\Mistral;
use Mindwave\Mindwave\Contracts\LLM;

/**
 * Mistral AI LLM Driver
 *
 * Note: Streaming is not currently implemented for this driver.
 * The streamText() method will throw a BadMethodCallException.
 * This may be added in a future version if the underlying client supports it.
 */
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
    ) {}

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
