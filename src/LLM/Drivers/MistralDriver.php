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
    ) {
    }

    public function model(string $model): self
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

    public function generateText(string $prompt): ?string
    {
        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        return $response->content;
    }

    public function chat(array $messages, array $options = []): \Mindwave\Mindwave\LLM\Responses\ChatResponse
    {
        $response = $this->client->chat()->create(array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'maxTokens' => $this->maxTokens,
            'safeMode' => $this->safeMode,
            'randomSeed' => $this->randomSeed
        ], $options));

        return new \Mindwave\Mindwave\LLM\Responses\ChatResponse(
            content: $response->choices[0]->message->content,
            role: $response->choices[0]->message->role,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            finishReason: $response->choices[0]->finishReason,
            model: $response->model,
            raw: (array) $response,
        );
    }
}
