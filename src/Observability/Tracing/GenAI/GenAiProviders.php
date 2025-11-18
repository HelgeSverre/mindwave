<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\GenAI;

/**
 * OpenTelemetry GenAI Provider Names
 *
 * Standard provider names for GenAI/LLM services as defined by
 * the OpenTelemetry semantic conventions.
 *
 * @see https://opentelemetry.io/docs/specs/semconv/gen-ai/gen-ai-spans/
 */
enum GenAiProviders: string
{
    /**
     * OpenAI
     *
     * Provider: OpenAI
     * Models: GPT-4, GPT-3.5, DALL-E, Whisper, TTS, Embeddings
     * API: api.openai.com
     */
    case OPENAI = 'openai';

    /**
     * Anthropic Claude
     *
     * Provider: Anthropic
     * Models: Claude 3 Opus, Claude 3 Sonnet, Claude 3 Haiku
     * API: api.anthropic.com
     */
    case ANTHROPIC = 'anthropic';

    /**
     * Mistral AI
     *
     * Provider: Mistral AI
     * Models: Mistral Large, Mistral Medium, Mistral Small, Mixtral
     * API: api.mistral.ai
     */
    case MISTRAL_AI = 'mistral_ai';

    /**
     * Google Cloud Platform - Gemini
     *
     * Provider: Google Cloud Platform
     * Models: Gemini Pro, Gemini Ultra
     * API: generativelanguage.googleapis.com
     */
    case GCP_GEMINI = 'gcp.gemini';

    /**
     * Google Cloud Platform - Vertex AI
     *
     * Provider: Google Cloud Platform
     * Models: PaLM 2, Codey, Imagen
     * API: {region}-aiplatform.googleapis.com
     */
    case GCP_VERTEX_AI = 'gcp.vertex_ai';

    /**
     * Amazon Web Services - Bedrock
     *
     * Provider: Amazon Web Services
     * Models: Claude, Titan, Jurassic, Command
     * API: bedrock-runtime.{region}.amazonaws.com
     */
    case AWS_BEDROCK = 'aws.bedrock';

    /**
     * Cohere
     *
     * Provider: Cohere
     * Models: Command, Generate, Embed, Rerank
     * API: api.cohere.ai
     */
    case COHERE = 'cohere';

    /**
     * Azure OpenAI
     *
     * Provider: Microsoft Azure
     * Models: GPT-4, GPT-3.5, Embeddings (hosted on Azure)
     * API: {resource-name}.openai.azure.com
     */
    case AZURE_OPENAI = 'azure.openai';

    /**
     * Hugging Face
     *
     * Provider: Hugging Face
     * Models: Various open-source models
     * API: api-inference.huggingface.co
     */
    case HUGGING_FACE = 'huggingface';

    /**
     * Ollama
     *
     * Provider: Ollama (local inference)
     * Models: Llama, Mistral, Phi, etc. (self-hosted)
     * API: localhost:11434
     */
    case OLLAMA = 'ollama';

    /**
     * Get the default server address for this provider
     *
     * @return string
     */
    public function getDefaultServerAddress(): string
    {
        return match ($this) {
            self::OPENAI => 'api.openai.com',
            self::ANTHROPIC => 'api.anthropic.com',
            self::MISTRAL_AI => 'api.mistral.ai',
            self::GCP_GEMINI => 'generativelanguage.googleapis.com',
            self::GCP_VERTEX_AI => 'aiplatform.googleapis.com',
            self::AWS_BEDROCK => 'bedrock-runtime.amazonaws.com',
            self::COHERE => 'api.cohere.ai',
            self::AZURE_OPENAI => 'openai.azure.com',
            self::HUGGING_FACE => 'api-inference.huggingface.co',
            self::OLLAMA => 'localhost',
        };
    }

    /**
     * Get the default server port for this provider
     *
     * @return int
     */
    public function getDefaultServerPort(): int
    {
        return match ($this) {
            self::OLLAMA => 11434,
            default => 443,
        };
    }

    /**
     * Get the display name for this provider
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::OPENAI => 'OpenAI',
            self::ANTHROPIC => 'Anthropic',
            self::MISTRAL_AI => 'Mistral AI',
            self::GCP_GEMINI => 'Google Gemini',
            self::GCP_VERTEX_AI => 'Google Vertex AI',
            self::AWS_BEDROCK => 'AWS Bedrock',
            self::COHERE => 'Cohere',
            self::AZURE_OPENAI => 'Azure OpenAI',
            self::HUGGING_FACE => 'Hugging Face',
            self::OLLAMA => 'Ollama',
        };
    }

    /**
     * Check if this provider is a cloud provider
     *
     * @return bool
     */
    public function isCloudProvider(): bool
    {
        return $this !== self::OLLAMA;
    }

    /**
     * Check if this provider is self-hosted
     *
     * @return bool
     */
    public function isSelfHosted(): bool
    {
        return $this === self::OLLAMA;
    }

    /**
     * Get supported operations for this provider
     *
     * @return array<GenAiOperations>
     */
    public function getSupportedOperations(): array
    {
        return match ($this) {
            self::OPENAI, self::AZURE_OPENAI => [
                GenAiOperations::CHAT,
                GenAiOperations::TEXT_COMPLETION,
                GenAiOperations::EMBEDDINGS,
                GenAiOperations::IMAGE_GENERATION,
                GenAiOperations::AUDIO_TRANSCRIPTION,
                GenAiOperations::TEXT_TO_SPEECH,
            ],
            self::ANTHROPIC => [
                GenAiOperations::CHAT,
            ],
            self::MISTRAL_AI => [
                GenAiOperations::CHAT,
                GenAiOperations::EMBEDDINGS,
            ],
            self::GCP_GEMINI, self::GCP_VERTEX_AI => [
                GenAiOperations::CHAT,
                GenAiOperations::TEXT_COMPLETION,
                GenAiOperations::EMBEDDINGS,
            ],
            self::COHERE => [
                GenAiOperations::CHAT,
                GenAiOperations::TEXT_COMPLETION,
                GenAiOperations::EMBEDDINGS,
            ],
            self::AWS_BEDROCK => [
                GenAiOperations::CHAT,
                GenAiOperations::TEXT_COMPLETION,
                GenAiOperations::EMBEDDINGS,
            ],
            self::HUGGING_FACE, self::OLLAMA => [
                GenAiOperations::CHAT,
                GenAiOperations::TEXT_COMPLETION,
                GenAiOperations::EMBEDDINGS,
            ],
        };
    }

    /**
     * Check if this provider supports a specific operation
     *
     * @param GenAiOperations $operation
     * @return bool
     */
    public function supportsOperation(GenAiOperations $operation): bool
    {
        return in_array($operation, $this->getSupportedOperations(), true);
    }

    /**
     * Get provider from string value (case-insensitive)
     *
     * @param string $value
     * @return self|null
     */
    public static function fromString(string $value): ?self
    {
        $value = strtolower($value);

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Try to detect provider from model name
     *
     * @param string $model
     * @return self|null
     */
    public static function detectFromModel(string $model): ?self
    {
        $model = strtolower($model);

        return match (true) {
            str_starts_with($model, 'gpt-') || str_starts_with($model, 'text-') || str_starts_with($model, 'dall-e') => self::OPENAI,
            str_starts_with($model, 'claude-') => self::ANTHROPIC,
            str_starts_with($model, 'mistral-') || str_starts_with($model, 'mixtral-') => self::MISTRAL_AI,
            str_starts_with($model, 'gemini-') => self::GCP_GEMINI,
            str_starts_with($model, 'command') || str_starts_with($model, 'embed-') => self::COHERE,
            default => null,
        };
    }
}
