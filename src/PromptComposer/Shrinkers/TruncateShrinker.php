<?php

namespace Mindwave\Mindwave\PromptComposer\Shrinkers;

use Mindwave\Mindwave\PromptComposer\Tokenizer\TokenizerInterface;

readonly class TruncateShrinker implements ShrinkerInterface
{
    public function __construct(
        protected TokenizerInterface $tokenizer,
        protected bool $sentenceAware = true,
    ) {}

    public function shrink(string $content, int $targetTokens, string $model): string
    {
        $currentTokens = $this->tokenizer->count($content, $model);

        // Already fits
        if ($currentTokens <= $targetTokens) {
            return $content;
        }

        // Sentence-aware truncation
        if ($this->sentenceAware) {
            return $this->truncateBySentence($content, $targetTokens, $model);
        }

        // Word-level truncation
        return $this->truncateByWord($content, $targetTokens, $model);
    }

    public function name(): string
    {
        return 'truncate';
    }

    /**
     * Truncate by complete sentences.
     */
    protected function truncateBySentence(string $content, int $targetTokens, string $model): string
    {
        // Split into sentences (simple approach)
        $sentences = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

        if (! $sentences) {
            return '';
        }

        $result = '';
        $currentTokens = 0;

        foreach ($sentences as $sentence) {
            $testContent = $result === '' ? $sentence : $result.' '.$sentence;
            $testTokens = $this->tokenizer->count($testContent, $model);

            if ($testTokens > $targetTokens) {
                break;
            }

            $result = $testContent;
            $currentTokens = $testTokens;
        }

        // If even one sentence is too long, fall back to word truncation
        if ($result === '') {
            return $this->truncateByWord($content, $targetTokens, $model);
        }

        return $result;
    }

    /**
     * Truncate by words.
     */
    protected function truncateByWord(string $content, int $targetTokens, string $model): string
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
