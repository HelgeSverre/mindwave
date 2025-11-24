<?php

namespace Mindwave\Mindwave\Contracts;

use Generator;
use Mindwave\Mindwave\Prompts\PromptTemplate;

interface LLM
{
    public function setSystemMessage(string $systemMessage): static;

    public function setOptions(array $options): static;

    public function generateText(string $prompt): ?string;

    public function generate(PromptTemplate $promptTemplate, array $inputs = []): mixed;

    /**
     * Send a chat message to the LLM.
     *
     * @param  array  $messages  The messages to send
     * @param  array  $options   Additional options (temperature, max_tokens, etc.)
     * @return \Mindwave\Mindwave\LLM\Responses\ChatResponse
     */
    public function chat(array $messages, array $options = []): \Mindwave\Mindwave\LLM\Responses\ChatResponse;

    /**
     * Generate text with streaming support.
     *
     * Yields text deltas as they are received from the LLM provider.
     * Each yielded value is a string containing the incremental content.
     *
     * @param  string  $prompt  The prompt to send to the LLM
     * @return Generator<string> A generator that yields text deltas
     *
     * @throws \BadMethodCallException If the driver does not support streaming
     */
    public function streamText(string $prompt): Generator;

    /**
     * Get the maximum context window size in tokens for the current model.
     *
     * Returns the total number of tokens that can be used for both input
     * and output combined. For example, GPT-4 Turbo returns 128,000 tokens,
     * GPT-5 returns 400,000 tokens, and GPT-4.1 returns 1,000,000 tokens.
     *
     * @return int The maximum number of tokens the model can handle
     */
    public function maxContextTokens(): int;
}
