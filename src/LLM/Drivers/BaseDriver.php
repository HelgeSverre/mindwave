<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use BadMethodCallException;
use Generator;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\Drivers\Concerns\HasOptions;
use Mindwave\Mindwave\LLM\Drivers\Concerns\HasSystemMessage;
use Mindwave\Mindwave\PromptComposer\Tokenizer\ModelTokenLimits;
use Mindwave\Mindwave\Prompts\PromptTemplate;

abstract class BaseDriver implements LLM
{
    use HasOptions;
    use HasSystemMessage;

    public function generate(PromptTemplate $promptTemplate, array $inputs = []): mixed
    {
        $formatted = $promptTemplate->format($inputs);

        $response = $this->generateText($formatted);

        return $promptTemplate->parse($response);
    }

    /**
     * Default implementation throws exception.
     * Drivers that support streaming should override this method.
     */
    public function streamText(string $prompt): Generator
    {
        throw new BadMethodCallException(
            sprintf('Streaming is not supported by the %s driver', static::class)
        );
    }

    /**
     * Get the maximum context window size for the current model.
     *
     * Uses the ModelTokenLimits utility to determine the token limit
     * based on the model identifier.
     */
    public function maxContextTokens(): int
    {
        return ModelTokenLimits::getContextWindow($this->model);
    }

    /**
     * Get the current model identifier.
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
