<?php

namespace Mindwave\Mindwave\PromptComposer\Shrinkers;

use Mindwave\Mindwave\PromptComposer\Tokenizer\TokenizerInterface;

class CompressShrinker implements ShrinkerInterface
{
    public function __construct(
        private readonly TokenizerInterface $tokenizer,
    ) {}

    public function shrink(string $content, int $targetTokens, string $model): string
    {
        $currentTokens = $this->tokenizer->count($content, $model);

        // Already fits
        if ($currentTokens <= $targetTokens) {
            return $content;
        }

        // Step 1: Remove extra whitespace
        $compressed = $this->removeExtraWhitespace($content);
        $currentTokens = $this->tokenizer->count($compressed, $model);

        if ($currentTokens <= $targetTokens) {
            return $compressed;
        }

        // Step 2: Remove markdown formatting
        $compressed = $this->removeMarkdownFormatting($compressed);
        $currentTokens = $this->tokenizer->count($compressed, $model);

        if ($currentTokens <= $targetTokens) {
            return $compressed;
        }

        // Step 3: Fall back to truncation
        return $this->truncateToTarget($compressed, $targetTokens, $model);
    }

    public function name(): string
    {
        return 'compress';
    }

    /**
     * Remove extra whitespace (multiple spaces, newlines, tabs).
     */
    private function removeExtraWhitespace(string $content): string
    {
        // Replace multiple newlines with single newline
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Replace multiple spaces with single space
        $content = preg_replace('/ {2,}/', ' ', $content);

        // Replace tabs with spaces
        $content = str_replace("\t", ' ', $content);

        return trim($content);
    }

    /**
     * Remove markdown formatting to save tokens.
     */
    private function removeMarkdownFormatting(string $content): string
    {
        // Remove bold/italic markers
        $content = preg_replace('/\*\*([^*]+)\*\*/', '$1', $content);
        $content = preg_replace('/\*([^*]+)\*/', '$1', $content);
        $content = preg_replace('/__([^_]+)__/', '$1', $content);
        $content = preg_replace('/_([^_]+)_/', '$1', $content);

        // Remove code blocks
        $content = preg_replace('/```[^`]*```/', '', $content);
        $content = preg_replace('/`([^`]+)`/', '$1', $content);

        // Remove headers
        $content = preg_replace('/^#{1,6}\s+/m', '', $content);

        // Remove links but keep text
        $content = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $content);

        return $content;
    }

    /**
     * Truncate to target token count.
     */
    private function truncateToTarget(string $content, int $targetTokens, string $model): string
    {
        $words = explode(' ', $content);
        $result = '';

        foreach ($words as $word) {
            $testContent = $result === '' ? $word : $result.' '.$word;
            $testTokens = $this->tokenizer->count($testContent, $model);

            if ($testTokens > $targetTokens) {
                break;
            }

            $result = $testContent;
        }

        return $result;
    }
}
