<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\GenAI;

use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionBuilder;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionCall;
use Mindwave\Mindwave\Prompts\PromptTemplate;

/**
 * LLM Driver Instrumentor Decorator
 *
 * This decorator wraps existing LLM drivers to automatically instrument all LLM calls
 * with OpenTelemetry tracing. It implements the transparent decorator pattern, making
 * tracing completely automatic without any changes to application code.
 *
 * Features:
 * - Transparent wrapping of LLM drivers
 * - Automatic provider and model detection
 * - All LLM operations instrumented
 * - Preserves original driver behavior
 * - Zero overhead when tracing is disabled
 *
 * Usage:
 * ```php
 * $driver = new OpenAI($client);
 * $instrumentedDriver = new LLMDriverInstrumentorDecorator(
 *     driver: $driver,
 *     instrumentor: $instrumentor,
 *     provider: 'openai'
 * );
 *
 * // All calls are now automatically traced
 * $response = $instrumentedDriver->generateText('Hello!');
 * ```
 *
 * The decorator can be applied at the driver level or at the manager level,
 * ensuring all LLM interactions are traced without explicit instrumentation calls.
 */
class LLMDriverInstrumentorDecorator implements LLM
{
    private LLM $driver;
    private GenAiInstrumentor $instrumentor;
    private string $provider;
    private ?string $model = null;
    private ?string $serverAddress = null;

    /**
     * @param LLM $driver The underlying LLM driver to wrap
     * @param GenAiInstrumentor $instrumentor The instrumentor for creating spans
     * @param string $provider Provider name (e.g., "openai", "anthropic", "mistral_ai")
     * @param string|null $model Optional default model name
     * @param string|null $serverAddress Optional server address override
     */
    public function __construct(
        LLM $driver,
        GenAiInstrumentor $instrumentor,
        string $provider,
        ?string $model = null,
        ?string $serverAddress = null
    ) {
        $this->driver = $driver;
        $this->instrumentor = $instrumentor;
        $this->provider = $provider;
        $this->model = $model;
        $this->serverAddress = $serverAddress;
    }

    /**
     * Set system message on the underlying driver
     *
     * @param string $systemMessage
     * @return static
     */
    public function setSystemMessage(string $systemMessage): static
    {
        $this->driver->setSystemMessage($systemMessage);

        return $this;
    }

    /**
     * Set options on the underlying driver
     *
     * @param array<string, mixed> $options
     * @return static
     */
    public function setOptions(array $options): static
    {
        $this->driver->setOptions($options);

        return $this;
    }

    /**
     * Generate text with automatic tracing
     *
     * This method automatically instruments the text generation call, capturing
     * request parameters, response attributes, and token usage.
     *
     * @param string $prompt
     * @return string|null
     */
    public function generateText(string $prompt): ?string
    {
        $model = $this->detectModel();
        $options = $this->extractOptions();

        return $this->instrumentor->instrumentTextCompletion(
            provider: $this->provider,
            model: $model,
            prompt: $prompt,
            options: $options,
            execute: fn() => $this->driver->generateText($prompt),
            serverAddress: $this->serverAddress
        );
    }

    /**
     * Generate with prompt template and automatic tracing
     *
     * @param PromptTemplate $promptTemplate
     * @param array<string, mixed> $inputs
     * @return mixed
     */
    public function generate(PromptTemplate $promptTemplate, array $inputs = []): mixed
    {
        $model = $this->detectModel();
        $options = $this->extractOptions();

        // Format the prompt for display purposes
        $formatted = $promptTemplate->format($inputs);

        return $this->instrumentor->instrumentTextCompletion(
            provider: $this->provider,
            model: $model,
            prompt: $formatted,
            options: $options,
            execute: fn() => $this->driver->generate($promptTemplate, $inputs),
            serverAddress: $this->serverAddress
        );
    }

    /**
     * Chat completion with automatic tracing (OpenAI-specific)
     *
     * This method instruments chat completions if the underlying driver supports it.
     * It checks for the 'chat' method using method_exists to maintain compatibility
     * with different driver implementations.
     *
     * @param array<array<string, mixed>>|string $prompt Messages or prompt string
     * @return mixed
     */
    public function chat(array|string $prompt): mixed
    {
        if (!method_exists($this->driver, 'chat')) {
            // Fallback to generateText if chat not supported
            return $this->generateText(is_array($prompt) ? json_encode($prompt) : $prompt);
        }

        $model = $this->detectModel();
        $options = $this->extractOptions();

        // Normalize to messages array
        $messages = is_array($prompt) ? $prompt : [['role' => 'user', 'content' => $prompt]];

        return $this->instrumentor->instrumentChatCompletion(
            provider: $this->provider,
            model: $model,
            messages: $messages,
            options: $options,
            execute: fn() => $this->driver->chat($prompt),
            serverAddress: $this->serverAddress
        );
    }

    /**
     * Function calling with automatic tracing (OpenAI-specific)
     *
     * This method instruments function calling if the underlying driver supports it.
     *
     * @param string $prompt
     * @param array<mixed>|FunctionBuilder $functions
     * @param string|null $requiredFunction
     * @return FunctionCall|string|null
     */
    public function functionCall(
        string $prompt,
        array|FunctionBuilder $functions,
        ?string $requiredFunction = 'auto'
    ): FunctionCall|string|null {
        if (!method_exists($this->driver, 'functionCall')) {
            // Fallback to generateText if functionCall not supported
            return $this->generateText($prompt);
        }

        $model = $this->detectModel();
        $options = $this->extractOptions();

        // Add function calling info to options for tracing
        $options['tool_choice'] = $requiredFunction ?? 'auto';
        $options['tools_count'] = $functions instanceof FunctionBuilder
            ? count($functions->build())
            : count($functions);

        return $this->instrumentor->instrumentChatCompletion(
            provider: $this->provider,
            model: $model,
            messages: [['role' => 'system', 'content' => $prompt]],
            options: $options,
            execute: fn() => $this->driver->functionCall($prompt, $functions, $requiredFunction),
            serverAddress: $this->serverAddress
        );
    }

    /**
     * Set the model name for tracing
     *
     * This is useful when the model is determined at runtime.
     *
     * @param string $model
     * @return static
     */
    public function model(string $model): static
    {
        $this->model = $model;

        // Set on underlying driver if it supports it
        if (method_exists($this->driver, 'model')) {
            $this->driver->model($model);
        }

        return $this;
    }

    /**
     * Set maximum tokens
     *
     * @param int $maxTokens
     * @return static
     */
    public function maxTokens(int $maxTokens): static
    {
        if (method_exists($this->driver, 'maxTokens')) {
            $this->driver->maxTokens($maxTokens);
        }

        return $this;
    }

    /**
     * Set temperature
     *
     * @param float $temperature
     * @return static
     */
    public function temperature(float $temperature): static
    {
        if (method_exists($this->driver, 'temperature')) {
            $this->driver->temperature($temperature);
        }

        return $this;
    }

    /**
     * Detect the current model from the driver
     *
     * This attempts to extract the model name from the underlying driver
     * using reflection if not explicitly set.
     *
     * @return string
     */
    private function detectModel(): string
    {
        // Use explicitly set model if available
        if ($this->model !== null) {
            return $this->model;
        }

        // Try to get model from driver property
        if (property_exists($this->driver, 'model')) {
            $reflection = new \ReflectionProperty($this->driver, 'model');
            $reflection->setAccessible(true);
            $model = $reflection->getValue($this->driver);

            if (is_string($model)) {
                return $model;
            }
        }

        // Fallback to provider default
        return 'unknown';
    }

    /**
     * Extract options from the underlying driver
     *
     * This attempts to extract LLM parameters (temperature, max_tokens, etc.)
     * from the underlying driver using reflection.
     *
     * @return array<string, mixed>
     */
    private function extractOptions(): array
    {
        $options = [];

        // Common LLM parameters to extract
        $parameters = [
            'temperature',
            'maxTokens' => 'max_tokens',
            'topP' => 'top_p',
            'topK' => 'top_k',
            'frequencyPenalty' => 'frequency_penalty',
            'presencePenalty' => 'presence_penalty',
            'stopSequences' => 'stop',
        ];

        foreach ($parameters as $property => $attributeName) {
            // Handle both string keys (property = attributeName) and mapped keys
            $propertyName = is_string($property) ? $property : $attributeName;
            $attrName = is_string($property) ? $attributeName : $property;

            if (property_exists($this->driver, $propertyName)) {
                try {
                    $reflection = new \ReflectionProperty($this->driver, $propertyName);
                    $reflection->setAccessible(true);
                    $value = $reflection->getValue($this->driver);

                    if ($value !== null) {
                        $options[$attrName] = $value;
                    }
                } catch (\ReflectionException $e) {
                    // Skip if property is not accessible
                    continue;
                }
            }
        }

        return $options;
    }

    /**
     * Get the underlying driver
     *
     * Useful for accessing driver-specific methods not in the LLM interface.
     *
     * @return LLM
     */
    public function getDriver(): LLM
    {
        return $this->driver;
    }

    /**
     * Get the instrumentor
     *
     * @return GenAiInstrumentor
     */
    public function getInstrumentor(): GenAiInstrumentor
    {
        return $this->instrumentor;
    }

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Set the provider name
     *
     * @param string $provider
     * @return static
     */
    public function setProvider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get the server address
     *
     * @return string|null
     */
    public function getServerAddress(): ?string
    {
        return $this->serverAddress;
    }

    /**
     * Set the server address
     *
     * @param string|null $serverAddress
     * @return static
     */
    public function setServerAddress(?string $serverAddress): static
    {
        $this->serverAddress = $serverAddress;

        return $this;
    }

    /**
     * Magic method to proxy unknown methods to the underlying driver
     *
     * This allows the decorator to be truly transparent, forwarding
     * any driver-specific methods that aren't part of the LLM interface.
     *
     * @param string $method
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (method_exists($this->driver, $method)) {
            return $this->driver->$method(...$arguments);
        }

        throw new \BadMethodCallException(
            sprintf('Method %s does not exist on %s', $method, get_class($this->driver))
        );
    }

    /**
     * Magic method to proxy property access to the underlying driver
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if (property_exists($this->driver, $name)) {
            return $this->driver->$name;
        }

        throw new \InvalidArgumentException(
            sprintf('Property %s does not exist on %s', $name, get_class($this->driver))
        );
    }

    /**
     * Magic method to proxy property setting to the underlying driver
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (property_exists($this->driver, $name)) {
            $this->driver->$name = $value;
        } else {
            throw new \InvalidArgumentException(
                sprintf('Property %s does not exist on %s', $name, get_class($this->driver))
            );
        }
    }
}
