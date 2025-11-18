<?php

namespace Mindwave\Mindwave\PromptComposer\Tokenizer;

class ModelTokenLimits
{
    /**
     * Get the context window size for a given model.
     */
    public static function getContextWindow(string $model): int
    {
        return match (true) {
            // OpenAI GPT-5 Models (400K context window)
            str_contains($model, 'gpt-5-mini') => 400_000,
            str_contains($model, 'gpt-5-nano') => 400_000,
            str_contains($model, 'gpt-5') => 400_000,

            // OpenAI GPT-4.1 Models (1M context window)
            str_contains($model, 'gpt-4.1-mini') => 1_000_000,
            str_contains($model, 'gpt-4.1-nano') => 1_000_000,
            str_contains($model, 'gpt-4.1') => 1_000_000,

            // OpenAI GPT-4 Models
            str_contains($model, 'gpt-4-turbo') => 128_000,
            str_contains($model, 'gpt-4o') => 128_000,
            str_contains($model, 'gpt-4-32k') => 32_768,
            str_contains($model, 'gpt-4') => 8_192,

            // OpenAI GPT-3.5 Models
            str_contains($model, 'gpt-3.5-turbo-16k') => 16_385,
            str_contains($model, 'gpt-3.5-turbo') => 16_385,

            // OpenAI O1 Models
            str_contains($model, 'o1-preview') => 128_000,
            str_contains($model, 'o1-mini') => 128_000,

            // Anthropic Claude Models
            str_contains($model, 'claude-3-5-sonnet') => 200_000,
            str_contains($model, 'claude-3-opus') => 200_000,
            str_contains($model, 'claude-3-sonnet') => 200_000,
            str_contains($model, 'claude-3-haiku') => 200_000,
            str_contains($model, 'claude-2.1') => 200_000,
            str_contains($model, 'claude-2.0') => 100_000,
            str_contains($model, 'claude-instant') => 100_000,

            // Mistral Models
            str_contains($model, 'mistral-large') => 128_000,
            str_contains($model, 'mistral-medium') => 32_000,
            str_contains($model, 'mistral-small') => 32_000,
            str_contains($model, 'mistral-tiny') => 32_000,
            str_contains($model, 'mixtral-8x7b') => 32_000,
            str_contains($model, 'mixtral-8x22b') => 64_000,

            // Google Gemini Models
            str_contains($model, 'gemini-1.5-pro') => 2_000_000,
            str_contains($model, 'gemini-1.5-flash') => 1_000_000,
            str_contains($model, 'gemini-pro') => 32_768,

            // Cohere Models
            str_contains($model, 'command-r-plus') => 128_000,
            str_contains($model, 'command-r') => 128_000,
            str_contains($model, 'command') => 4_096,

            // Default fallback
            default => 4_096,
        };
    }

    /**
     * Get the encoding name for a given model.
     */
    public static function getEncoding(string $model): string
    {
        return match (true) {
            // GPT-5 and GPT-4.1 use o200k_base (same as o1 models)
            str_contains($model, 'gpt-5') => 'o200k_base',
            str_contains($model, 'gpt-4.1') => 'o200k_base',

            // GPT-4 and newer use cl100k_base
            str_contains($model, 'gpt-4') => 'cl100k_base',
            str_contains($model, 'gpt-3.5-turbo') => 'cl100k_base',
            str_contains($model, 'o1-') => 'o200k_base',
            str_contains($model, 'text-embedding-ada-002') => 'cl100k_base',
            str_contains($model, 'text-embedding-3') => 'cl100k_base',

            // Legacy models
            str_contains($model, 'text-davinci-003') => 'p50k_base',
            str_contains($model, 'text-davinci-002') => 'p50k_base',
            str_contains($model, 'davinci') => 'r50k_base',
            str_contains($model, 'curie') => 'r50k_base',
            str_contains($model, 'babbage') => 'r50k_base',
            str_contains($model, 'ada') => 'r50k_base',

            // Default to cl100k_base for most modern models
            default => 'cl100k_base',
        };
    }

    /**
     * Get all supported models with their limits.
     *
     * @return array<string, int>
     */
    public static function all(): array
    {
        return [
            'gpt-5' => 400_000,
            'gpt-5-mini' => 400_000,
            'gpt-5-nano' => 400_000,
            'gpt-4.1' => 1_000_000,
            'gpt-4.1-mini' => 1_000_000,
            'gpt-4.1-nano' => 1_000_000,
            'gpt-4-turbo' => 128_000,
            'gpt-4o' => 128_000,
            'gpt-4-32k' => 32_768,
            'gpt-4' => 8_192,
            'gpt-3.5-turbo-16k' => 16_385,
            'gpt-3.5-turbo' => 16_385,
            'o1-preview' => 128_000,
            'o1-mini' => 128_000,
            'claude-3-5-sonnet' => 200_000,
            'claude-3-opus' => 200_000,
            'claude-3-sonnet' => 200_000,
            'claude-3-haiku' => 200_000,
            'claude-2.1' => 200_000,
            'claude-2.0' => 100_000,
            'mistral-large' => 128_000,
            'mistral-medium' => 32_000,
            'mixtral-8x7b' => 32_000,
            'mixtral-8x22b' => 64_000,
            'gemini-1.5-pro' => 2_000_000,
            'gemini-1.5-flash' => 1_000_000,
            'gemini-pro' => 32_768,
            'command-r-plus' => 128_000,
            'command-r' => 128_000,
        ];
    }
}
