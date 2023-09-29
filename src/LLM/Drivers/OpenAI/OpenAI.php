<?php

namespace Mindwave\Mindwave\LLM\Drivers\OpenAI;

use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionBuilder;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionCall;
use Mindwave\Mindwave\Prompts\PromptTemplate;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use OpenAI\Responses\Completions\CreateResponse as CompletionResponse;

class OpenAI implements LLM
{
    public function __construct(
        protected Client $client,
        protected Model $model = Model::turbo16k,
        protected ?string $systemMessage = null,
        protected int $maxTokens = 800,
        protected float $temperature = 0.7,
    ) {
    }

    public function model(string|Model $model): self
    {
        $this->model = Model::tryFrom($model);

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

    public function setSystemMessage(string $systemMessage)
    {
        $this->systemMessage = $systemMessage;
    }

    public function generate(PromptTemplate $promptTemplate, array $inputs = []): mixed
    {
        $formatted = $promptTemplate->format($inputs);

        $response = $this->generateText($formatted);

        return $promptTemplate->parse($response);
    }

    public function functionCall(string $prompt, array|FunctionBuilder $functions, ?string $requiredFunction = 'auto'): FunctionCall|string|null
    {
        /** @var ChatResponse $response */
        $response = $this->client->chat()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model->value,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
            ],
            'functions' => $functions instanceof FunctionBuilder ? $functions->build() : $functions,
            'function_call' => match ($requiredFunction) {
                null, 'auto' => 'auto',
                'none' => 'none',
                default => ['name' => $requiredFunction],
            },
        ]);

        $choice = $response->choices[0];

        if ($choice->finishReason === 'function_call') {
            return new FunctionCall(
                name: $choice->message->functionCall->name,
                arguments: rescue(fn () => json_decode($choice->message->functionCall->arguments, true), report: false),
                rawArguments: $choice->message->functionCall->arguments,
            );
        }

        return $this->extractResponseText($response);
    }

    public function generateText(string $prompt): ?string
    {
        $response = $this->model?->isCompletionModel()
            ? $this->completion($prompt)
            : $this->chat($prompt);

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
            'model' => $this->model->value,
            'messages' => $this->systemMessage
                ? [
                    ['role' => 'system', 'content' => $this->systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ]
                : [
                    ['role' => 'system', 'content' => $prompt],
                ],
        ]);
    }

    public function completion($prompt): CompletionResponse
    {

        return $this->client->completions()->create([
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'model' => $this->model->value,
            'prompt' => $prompt,
        ]);
    }
}
