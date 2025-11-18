<?php

namespace Mindwave\Mindwave\Context;

use Illuminate\Support\Collection;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer;

/**
 * Context Collection
 *
 * Laravel Collection extension with context-specific utilities.
 */
class ContextCollection extends Collection
{
    /**
     * Format collection as prompt-ready string.
     *
     * @param  string  $format  Format type: 'numbered', 'markdown', 'json'
     * @return string Formatted context string
     */
    public function formatForPrompt(string $format = 'numbered'): string
    {
        return match ($format) {
            'numbered' => $this->formatNumbered(),
            'markdown' => $this->formatMarkdown(),
            'json' => $this->formatJson(),
            default => $this->formatNumbered(),
        };
    }

    /**
     * Format as numbered list.
     */
    protected function formatNumbered(): string
    {
        $parts = [];

        foreach ($this->items as $index => $item) {
            $parts[] = sprintf(
                "[%d] (score: %s, source: %s)\n%s",
                $index + 1,
                number_format($item->score, 2, '.', ''),
                $item->source,
                $item->content
            );
        }

        return implode("\n\n", $parts);
    }

    /**
     * Format as markdown sections.
     */
    protected function formatMarkdown(): string
    {
        $parts = [];

        foreach ($this->items as $index => $item) {
            $parts[] = sprintf(
                "### Context %d (score: %s)\n\n%s\n\n*Source: %s*",
                $index + 1,
                number_format($item->score, 2, '.', ''),
                $item->content,
                $item->source
            );
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Format as JSON string.
     */
    protected function formatJson(): string
    {
        return json_encode($this->map(fn ($item) => $item->toArray()), JSON_PRETTY_PRINT);
    }

    /**
     * Remove duplicate items by content hash.
     */
    public function deduplicate(): self
    {
        $seen = [];
        $unique = [];

        foreach ($this->items as $item) {
            $hash = md5($item->content);

            if (! isset($seen[$hash])) {
                $seen[$hash] = true;
                $unique[] = $item;
            } elseif ($item->score > ($unique[array_search($hash, array_keys($seen))]->score ?? 0)) {
                // Keep higher scored version
                $unique[array_search($hash, array_keys($seen))] = $item;
            }
        }

        return new static($unique);
    }

    /**
     * Re-rank by score descending.
     */
    public function rerank(): self
    {
        return $this->sortByDesc('score')->values();
    }

    /**
     * Truncate content to fit within token limit.
     *
     * @param  int  $maxTokens  Maximum tokens allowed
     * @param  string  $model  Model to use for tokenization (default: gpt-4)
     */
    public function truncateToTokens(int $maxTokens, string $model = 'gpt-4'): self
    {
        $tokenizer = app(TiktokenTokenizer::class);
        $currentTokens = 0;
        $truncated = [];

        foreach ($this->items as $item) {
            $itemTokens = $tokenizer->count($item->content, $model);

            if ($currentTokens + $itemTokens <= $maxTokens) {
                $truncated[] = $item;
                $currentTokens += $itemTokens;
            } else {
                // Try to fit a truncated version
                $remainingTokens = $maxTokens - $currentTokens;

                if ($remainingTokens > 50) { // Only if we have meaningful space left
                    // Encode, truncate tokens, decode
                    $tokens = $tokenizer->encode($item->content, $model);
                    $truncatedTokens = array_slice($tokens, 0, $remainingTokens);
                    $truncatedContent = $tokenizer->decode($truncatedTokens, $model);

                    $newItem = new ContextItem(
                        $truncatedContent,
                        $item->score,
                        $item->source,
                        array_merge($item->metadata, [
                            'truncated' => true,
                            'original_length' => strlen($item->content),
                        ])
                    );
                    $truncated[] = $newItem;
                }

                break; // Stop adding items
            }
        }

        return new static($truncated);
    }

    /**
     * Get total token count of all items.
     *
     * @param  string  $model  Model to use for tokenization (default: gpt-4)
     */
    public function getTotalTokens(string $model = 'gpt-4'): int
    {
        $tokenizer = app(TiktokenTokenizer::class);
        $total = 0;

        foreach ($this->items as $item) {
            $total += $tokenizer->count($item->content, $model);
        }

        return $total;
    }
}
