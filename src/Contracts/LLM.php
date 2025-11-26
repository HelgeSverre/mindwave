<?php

namespace Mindwave\Mindwave\Contracts;

use Generator;
use Mindwave\Mindwave\Prompts\PromptTemplate;

/**
 * Contract for LLM (Large Language Model) drivers.
 *
 * This interface defines the core methods that all LLM drivers must implement
 * to interact with language models (OpenAI, Anthropic, Mistral, etc.).
 */
interface LLM
{
    /**
     * Set the system message/prompt for the conversation.
     *
     * The system message provides context and instructions to guide the model's behavior.
     *
     * @param  string  $systemMessage  The system prompt content
     * @return static Returns self for method chaining
     */
    public function setSystemMessage(string $systemMessage): static;

    /**
     * Set additional options for API requests.
     *
     * @param  array  $options  Driver-specific options (e.g., temperature, top_p)
     * @return static Returns self for method chaining
     */
    public function setOptions(array $options): static;

    /**
     * Generate plain text from a prompt.
     *
     * This is the simplest way to get a response from the LLM. For more control
     * over the response format, use chat() instead.
     *
     * @param  string  $prompt  The input prompt
     * @return string|null The generated text, or null if generation failed
     */
    public function generateText(string $prompt): ?string;

    /**
     * Generate a response using a prompt template with output parsing.
     *
     * The return type depends on the OutputParser configured in the template.
     * For example, a JSON parser returns an array, a list parser returns an array of strings.
     *
     * @param  PromptTemplate  $promptTemplate  Template with optional OutputParser
     * @param  array  $inputs  Variables to substitute into the template
     * @return mixed The parsed output (type depends on the OutputParser used)
     */
    public function generate(PromptTemplate $promptTemplate, array $inputs = []): mixed;

    /**
     * Send a chat completion request with full response metadata.
     *
     * Use this method when you need detailed response information like
     * token counts, finish reason, or the raw API response.
     *
     * @param  array<array{role: string, content: string}>  $messages  Array of messages with role and content
     * @param  array  $options  Additional options (temperature, max_tokens, etc.)
     * @return \Mindwave\Mindwave\LLM\Responses\ChatResponse Structured response with content, tokens, and metadata
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

    /**
     * Get the current model identifier.
     *
     * Returns the model ID being used by this driver (e.g., 'gpt-4', 'claude-3-opus').
     *
     * @return string The model identifier
     */
    public function getModel(): string;
}
