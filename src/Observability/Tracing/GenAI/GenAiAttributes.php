<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\GenAI;

/**
 * OpenTelemetry GenAI Semantic Conventions Attributes
 *
 * This class defines standard attribute names for LLM/GenAI observability
 * following the OpenTelemetry semantic conventions for GenAI systems.
 *
 * Reference: https://opentelemetry.io/docs/specs/semconv/gen-ai/
 *
 * @see https://opentelemetry.io/docs/specs/semconv/gen-ai/gen-ai-spans/
 */
final class GenAiAttributes
{
    /**
     * Operation Attributes
     *
     * The name of the operation being performed.
     * Examples: "chat", "text_completion", "embeddings", "execute_tool"
     */
    public const GEN_AI_OPERATION_NAME = 'gen_ai.operation.name';

    /**
     * Provider Attributes
     *
     * The name of the GenAI provider.
     * Examples: "openai", "anthropic", "mistral_ai", "gcp.gemini"
     */
    public const GEN_AI_PROVIDER_NAME = 'gen_ai.provider.name';

    /**
     * Request Attributes - Model
     *
     * The name of the model being used for the request.
     * Example: "gpt-4", "claude-3-opus", "mistral-large"
     */
    public const GEN_AI_REQUEST_MODEL = 'gen_ai.request.model';

    /**
     * Request Attributes - Temperature
     *
     * The temperature sampling parameter (0.0 to 2.0).
     * Controls randomness in the model's output.
     */
    public const GEN_AI_REQUEST_TEMPERATURE = 'gen_ai.request.temperature';

    /**
     * Request Attributes - Maximum Tokens
     *
     * Maximum number of tokens to generate in the completion.
     */
    public const GEN_AI_REQUEST_MAX_TOKENS = 'gen_ai.request.max_tokens';

    /**
     * Request Attributes - Top P
     *
     * The top_p sampling parameter (nucleus sampling).
     * Value between 0 and 1.
     */
    public const GEN_AI_REQUEST_TOP_P = 'gen_ai.request.top_p';

    /**
     * Request Attributes - Top K
     *
     * The top_k sampling parameter.
     * Only sample from the top K options for each subsequent token.
     */
    public const GEN_AI_REQUEST_TOP_K = 'gen_ai.request.top_k';

    /**
     * Request Attributes - Frequency Penalty
     *
     * Penalize new tokens based on their frequency in the text so far.
     * Value between -2.0 and 2.0.
     */
    public const GEN_AI_REQUEST_FREQUENCY_PENALTY = 'gen_ai.request.frequency_penalty';

    /**
     * Request Attributes - Presence Penalty
     *
     * Penalize new tokens based on whether they appear in the text so far.
     * Value between -2.0 and 2.0.
     */
    public const GEN_AI_REQUEST_PRESENCE_PENALTY = 'gen_ai.request.presence_penalty';

    /**
     * Request Attributes - Stop Sequences
     *
     * Up to 4 sequences where the API will stop generating further tokens.
     */
    public const GEN_AI_REQUEST_STOP_SEQUENCES = 'gen_ai.request.stop_sequences';

    /**
     * Response Attributes - Response ID
     *
     * The unique identifier for the response from the LLM provider.
     * Example: "chatcmpl-123"
     */
    public const GEN_AI_RESPONSE_ID = 'gen_ai.response.id';

    /**
     * Response Attributes - Response Model
     *
     * The name of the model that generated the response.
     * May differ from request model (e.g., "gpt-4-0613" vs "gpt-4").
     */
    public const GEN_AI_RESPONSE_MODEL = 'gen_ai.response.model';

    /**
     * Response Attributes - Finish Reasons
     *
     * Array of reasons why the model stopped generating tokens.
     * Examples: ["stop"], ["length"], ["tool_calls"], ["content_filter"]
     */
    public const GEN_AI_RESPONSE_FINISH_REASONS = 'gen_ai.response.finish_reasons';

    /**
     * Usage Attributes - Input Tokens
     *
     * Number of tokens in the input/prompt.
     */
    public const GEN_AI_USAGE_INPUT_TOKENS = 'gen_ai.usage.input_tokens';

    /**
     * Usage Attributes - Output Tokens
     *
     * Number of tokens in the generated output/completion.
     */
    public const GEN_AI_USAGE_OUTPUT_TOKENS = 'gen_ai.usage.output_tokens';

    /**
     * Usage Attributes - Total Tokens
     *
     * Total number of tokens used (input + output).
     */
    public const GEN_AI_USAGE_TOTAL_TOKENS = 'gen_ai.usage.total_tokens';

    /**
     * Usage Attributes - Cache Read Tokens
     *
     * Number of tokens read from cache (Anthropic Claude specific).
     */
    public const GEN_AI_USAGE_CACHE_READ_TOKENS = 'gen_ai.usage.cache_read_tokens';

    /**
     * Usage Attributes - Cache Creation Tokens
     *
     * Number of tokens used to create cache entries (Anthropic Claude specific).
     */
    public const GEN_AI_USAGE_CACHE_CREATION_TOKENS = 'gen_ai.usage.cache_creation_tokens';

    /**
     * Content Attributes - System Instructions
     *
     * The system instructions/prompt provided to the model.
     * SENSITIVE: Opt-in only, may contain PII or business logic.
     */
    public const GEN_AI_SYSTEM_INSTRUCTIONS = 'gen_ai.system_instructions';

    /**
     * Content Attributes - Input Messages
     *
     * Array of input messages sent to the model.
     * SENSITIVE: Opt-in only, contains user data.
     *
     * Format: [{"role": "user", "content": "..."}]
     */
    public const GEN_AI_INPUT_MESSAGES = 'gen_ai.input.messages';

    /**
     * Content Attributes - Output Messages
     *
     * Array of messages generated by the model.
     * SENSITIVE: Opt-in only, contains AI responses.
     *
     * Format: [{"role": "assistant", "content": "..."}]
     */
    public const GEN_AI_OUTPUT_MESSAGES = 'gen_ai.output.messages';

    /**
     * Tool/Function Calling Attributes - Tool Call Name
     *
     * The name of the tool/function being called.
     */
    public const GEN_AI_TOOL_CALL_NAME = 'gen_ai.tool.call.name';

    /**
     * Tool/Function Calling Attributes - Tool Call Arguments
     *
     * The arguments passed to the tool/function.
     * SENSITIVE: May contain user data.
     */
    public const GEN_AI_TOOL_CALL_ARGUMENTS = 'gen_ai.tool.call.arguments';

    /**
     * Tool/Function Calling Attributes - Tool Call Result
     *
     * The result returned from the tool/function execution.
     * SENSITIVE: May contain business data.
     */
    public const GEN_AI_TOOL_CALL_RESULT = 'gen_ai.tool.call.result';

    /**
     * Embeddings Attributes - Input Text
     *
     * The text input for embeddings generation.
     * SENSITIVE: May contain user data.
     */
    public const GEN_AI_EMBEDDINGS_INPUT = 'gen_ai.embeddings.input';

    /**
     * Embeddings Attributes - Dimension
     *
     * The dimension/size of the generated embedding vector.
     */
    public const GEN_AI_EMBEDDINGS_DIMENSION = 'gen_ai.embeddings.dimension';

    /**
     * Legacy LLM Attributes - Total Tokens (backward compatibility)
     *
     * @deprecated Use GEN_AI_USAGE_TOTAL_TOKENS instead
     */
    public const LLM_USAGE_TOTAL_TOKENS = 'llm.usage.total_tokens';

    /**
     * Server Attributes - Address
     *
     * The server address/hostname for the GenAI provider.
     * Example: "api.openai.com"
     */
    public const SERVER_ADDRESS = 'server.address';

    /**
     * Server Attributes - Port
     *
     * The server port for the GenAI provider.
     * Example: 443
     */
    public const SERVER_PORT = 'server.port';

    /**
     * Get all required attribute names
     *
     * These attributes should be present on every GenAI span.
     *
     * @return array<string>
     */
    public static function getRequiredAttributes(): array
    {
        return [
            self::GEN_AI_OPERATION_NAME,
            self::GEN_AI_PROVIDER_NAME,
            self::GEN_AI_REQUEST_MODEL,
        ];
    }

    /**
     * Get all request attribute names
     *
     * @return array<string>
     */
    public static function getRequestAttributes(): array
    {
        return [
            self::GEN_AI_REQUEST_MODEL,
            self::GEN_AI_REQUEST_TEMPERATURE,
            self::GEN_AI_REQUEST_MAX_TOKENS,
            self::GEN_AI_REQUEST_TOP_P,
            self::GEN_AI_REQUEST_TOP_K,
            self::GEN_AI_REQUEST_FREQUENCY_PENALTY,
            self::GEN_AI_REQUEST_PRESENCE_PENALTY,
            self::GEN_AI_REQUEST_STOP_SEQUENCES,
        ];
    }

    /**
     * Get all response attribute names
     *
     * @return array<string>
     */
    public static function getResponseAttributes(): array
    {
        return [
            self::GEN_AI_RESPONSE_ID,
            self::GEN_AI_RESPONSE_MODEL,
            self::GEN_AI_RESPONSE_FINISH_REASONS,
        ];
    }

    /**
     * Get all usage/token attribute names
     *
     * @return array<string>
     */
    public static function getUsageAttributes(): array
    {
        return [
            self::GEN_AI_USAGE_INPUT_TOKENS,
            self::GEN_AI_USAGE_OUTPUT_TOKENS,
            self::GEN_AI_USAGE_TOTAL_TOKENS,
            self::GEN_AI_USAGE_CACHE_READ_TOKENS,
            self::GEN_AI_USAGE_CACHE_CREATION_TOKENS,
        ];
    }

    /**
     * Get all sensitive attribute names
     *
     * These attributes should be redacted by default unless explicitly enabled.
     *
     * @return array<string>
     */
    public static function getSensitiveAttributes(): array
    {
        return [
            self::GEN_AI_SYSTEM_INSTRUCTIONS,
            self::GEN_AI_INPUT_MESSAGES,
            self::GEN_AI_OUTPUT_MESSAGES,
            self::GEN_AI_TOOL_CALL_ARGUMENTS,
            self::GEN_AI_TOOL_CALL_RESULT,
            self::GEN_AI_EMBEDDINGS_INPUT,
        ];
    }

    /**
     * Get all tool-related attribute names
     *
     * @return array<string>
     */
    public static function getToolAttributes(): array
    {
        return [
            self::GEN_AI_TOOL_CALL_NAME,
            self::GEN_AI_TOOL_CALL_ARGUMENTS,
            self::GEN_AI_TOOL_CALL_RESULT,
        ];
    }

    /**
     * Get all embeddings-related attribute names
     *
     * @return array<string>
     */
    public static function getEmbeddingsAttributes(): array
    {
        return [
            self::GEN_AI_EMBEDDINGS_INPUT,
            self::GEN_AI_EMBEDDINGS_DIMENSION,
        ];
    }

    /**
     * Check if an attribute is sensitive
     *
     * @param string $attributeName
     * @return bool
     */
    public static function isSensitive(string $attributeName): bool
    {
        return in_array($attributeName, self::getSensitiveAttributes(), true);
    }

    /**
     * Check if an attribute is required
     *
     * @param string $attributeName
     * @return bool
     */
    public static function isRequired(string $attributeName): bool
    {
        return in_array($attributeName, self::getRequiredAttributes(), true);
    }
}
