<?php

namespace Mindwave\Mindwave\PromptComposer\Shrinkers;

/**
 * Available shrinker types for prompt section content reduction.
 *
 * Use these constants instead of magic strings when specifying shrinkers
 * in the PromptComposer to get IDE autocompletion and type safety.
 */
enum ShrinkerType: string
{
    /**
     * Truncate content by removing text from the end.
     * Attempts sentence-aware truncation before falling back to word-level.
     */
    case Truncate = 'truncate';

    /**
     * Compress content by removing formatting and extra whitespace.
     * Falls back to truncation if compression is insufficient.
     */
    case Compress = 'compress';

    /**
     * Get a human-readable description of what this shrinker does.
     */
    public function description(): string
    {
        return match ($this) {
            self::Truncate => 'Removes content from the end, respecting sentence boundaries.',
            self::Compress => 'Removes formatting and whitespace before falling back to truncation.',
        };
    }
}
