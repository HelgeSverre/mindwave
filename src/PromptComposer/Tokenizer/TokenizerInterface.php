<?php

namespace Mindwave\Mindwave\PromptComposer\Tokenizer;

interface TokenizerInterface
{
    /**
     * Count the number of tokens in the given text for the specified model.
     */
    public function count(string $text, string $model): int;

    /**
     * Encode text into tokens for the specified model.
     *
     * @return array<int>
     */
    public function encode(string $text, string $model): array;

    /**
     * Decode tokens back into text for the specified model.
     *
     * @param  array<int>  $tokens
     */
    public function decode(array $tokens, string $model): string;

    /**
     * Get the maximum context window size for the specified model.
     */
    public function getContextWindow(string $model): int;

    /**
     * Check if the model is supported by this tokenizer.
     */
    public function supports(string $model): bool;
}
