<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\GenAI;

/**
 * OpenTelemetry GenAI Operation Types
 *
 * Standard operation names for GenAI/LLM operations as defined by
 * the OpenTelemetry semantic conventions.
 *
 * @see https://opentelemetry.io/docs/specs/semconv/gen-ai/gen-ai-spans/
 */
enum GenAiOperations: string
{
    /**
     * Chat Completion Operation
     *
     * Used for chat-based interactions with conversational models.
     * Examples: OpenAI Chat Completions, Anthropic Messages API
     */
    case CHAT = 'chat';

    /**
     * Text Completion Operation
     *
     * Used for traditional text completion (non-chat).
     * Examples: OpenAI legacy completions, text generation
     */
    case TEXT_COMPLETION = 'text_completion';

    /**
     * Embeddings Generation Operation
     *
     * Used for generating vector embeddings from text.
     * Examples: OpenAI Embeddings, text-embedding-ada-002
     */
    case EMBEDDINGS = 'embeddings';

    /**
     * Tool/Function Execution Operation
     *
     * Used when the LLM calls a tool or function.
     * Part of function calling / tool use workflows.
     */
    case EXECUTE_TOOL = 'execute_tool';

    /**
     * Image Generation Operation
     *
     * Used for generating images from text prompts.
     * Examples: DALL-E, Stable Diffusion
     */
    case IMAGE_GENERATION = 'image_generation';

    /**
     * Audio Transcription Operation
     *
     * Used for speech-to-text operations.
     * Examples: OpenAI Whisper
     */
    case AUDIO_TRANSCRIPTION = 'audio_transcription';

    /**
     * Audio Translation Operation
     *
     * Used for audio translation operations.
     */
    case AUDIO_TRANSLATION = 'audio_translation';

    /**
     * Text-to-Speech Operation
     *
     * Used for generating speech from text.
     * Examples: OpenAI TTS
     */
    case TEXT_TO_SPEECH = 'text_to_speech';

    /**
     * Get all chat-related operations
     *
     * @return array<self>
     */
    public static function getChatOperations(): array
    {
        return [
            self::CHAT,
            self::TEXT_COMPLETION,
        ];
    }

    /**
     * Get all vector-related operations
     *
     * @return array<self>
     */
    public static function getVectorOperations(): array
    {
        return [
            self::EMBEDDINGS,
        ];
    }

    /**
     * Get all audio-related operations
     *
     * @return array<self>
     */
    public static function getAudioOperations(): array
    {
        return [
            self::AUDIO_TRANSCRIPTION,
            self::AUDIO_TRANSLATION,
            self::TEXT_TO_SPEECH,
        ];
    }

    /**
     * Check if this operation is a chat operation
     */
    public function isChat(): bool
    {
        return in_array($this, self::getChatOperations(), true);
    }

    /**
     * Check if this operation is a vector operation
     */
    public function isVector(): bool
    {
        return in_array($this, self::getVectorOperations(), true);
    }

    /**
     * Check if this operation is an audio operation
     */
    public function isAudio(): bool
    {
        return in_array($this, self::getAudioOperations(), true);
    }

    /**
     * Check if this operation supports token usage tracking
     */
    public function supportsTokenUsage(): bool
    {
        return match ($this) {
            self::CHAT,
            self::TEXT_COMPLETION,
            self::EMBEDDINGS => true,
            default => false,
        };
    }

    /**
     * Get a human-readable description of the operation
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CHAT => 'Chat completion with conversational models',
            self::TEXT_COMPLETION => 'Text completion generation',
            self::EMBEDDINGS => 'Vector embeddings generation',
            self::EXECUTE_TOOL => 'Tool or function execution',
            self::IMAGE_GENERATION => 'Image generation from text',
            self::AUDIO_TRANSCRIPTION => 'Speech-to-text transcription',
            self::AUDIO_TRANSLATION => 'Audio translation',
            self::TEXT_TO_SPEECH => 'Text-to-speech synthesis',
        };
    }
}
