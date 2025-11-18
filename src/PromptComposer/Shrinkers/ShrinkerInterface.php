<?php

namespace Mindwave\Mindwave\PromptComposer\Shrinkers;

interface ShrinkerInterface
{
    /**
     * Shrink the content to fit within the target token count.
     */
    public function shrink(string $content, int $targetTokens, string $model): string;

    /**
     * Get the name of this shrinker.
     */
    public function name(): string;
}
