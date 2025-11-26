<?php

namespace Mindwave\Mindwave\PromptComposer;

use Mindwave\Mindwave\Context\ContextPipeline;
use Mindwave\Mindwave\Context\Contracts\ContextSource;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\PromptComposer\Shrinkers\CompressShrinker;
use Mindwave\Mindwave\PromptComposer\Shrinkers\ShrinkerInterface;
use Mindwave\Mindwave\PromptComposer\Shrinkers\ShrinkerType;
use Mindwave\Mindwave\PromptComposer\Shrinkers\TruncateShrinker;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TokenizerInterface;

class PromptComposer
{
    /** @var array<Section> */
    private array $sections = [];

    private int $reservedOutputTokens = 0;

    private ?string $model = null;

    private bool $fitted = false;

    /** @var array<string, ShrinkerInterface> */
    private array $shrinkers = [];

    public function __construct(
        private readonly TokenizerInterface $tokenizer,
        private readonly ?LLM $llm = null,
    ) {
        $this->registerDefaultShrinkers();
    }

    /**
     * Reserve tokens for the output/completion.
     */
    public function reserveOutputTokens(int $tokens): self
    {
        $this->reservedOutputTokens = $tokens;

        return $this;
    }

    /**
     * Set the model to use for token counting and limits.
     */
    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Add a section to the prompt.
     *
     * @param  string  $name  Section name (e.g., 'system', 'user', 'context')
     * @param  string|array  $content  Section content
     * @param  int  $priority  Priority (higher = more important, less likely to shrink)
     * @param  ShrinkerType|string|null  $shrinker  Shrinker to use when fitting to context window
     * @param  array  $metadata  Additional metadata
     */
    public function section(
        string $name,
        string|array $content,
        int $priority = 50,
        ShrinkerType|string|null $shrinker = null,
        array $metadata = []
    ): self {
        // Normalize ShrinkerType enum to string for internal storage
        $shrinkerKey = $shrinker instanceof ShrinkerType ? $shrinker->value : $shrinker;

        $this->sections[] = Section::make($name, $content, $priority, $shrinkerKey, $metadata);
        $this->fitted = false;

        return $this;
    }

    /**
     * Add a context section (convenience method).
     *
     * Supports:
     * - Plain string or array content
     * - ContextSource instance (searches and formats results)
     * - ContextPipeline instance (searches multiple sources)
     *
     * @param  string|array|ContextSource|ContextPipeline  $content  Content or context source
     * @param  int  $priority  Section priority (higher = more important)
     * @param  string|null  $query  Search query (optional, auto-extracted from user section if not provided)
     * @param  int  $limit  Maximum context items to retrieve (default: 5)
     */
    public function context(
        string|array|ContextSource|ContextPipeline $content,
        int $priority = 50,
        ?string $query = null,
        int $limit = 5
    ): self {
        // If content is a ContextSource or ContextPipeline, search and format
        if ($content instanceof ContextSource || $content instanceof ContextPipeline) {
            // Auto-extract query from user section if not provided
            $query ??= $this->extractQueryFromSections();

            // Initialize and search
            if ($content instanceof ContextSource) {
                $content->initialize();
                $results = $content->search($query, $limit);
            } else {
                $results = $content->search($query, $limit);
            }

            // Format results as string
            $content = $results->formatForPrompt();
        }

        return $this->section('context', $content, $priority, ShrinkerType::Truncate);
    }

    /**
     * Extract search query from the most recent user section.
     */
    private function extractQueryFromSections(): string
    {
        // Get most recent user message
        foreach (array_reverse($this->sections) as $section) {
            if ($section->name === 'user' || str_contains(strtolower($section->name), 'user')) {
                return is_string($section->content)
                    ? $section->content
                    : (is_array($section->content) ? implode(' ', array_map(fn ($item) => is_string($item) ? $item : ($item['content'] ?? ''), $section->content)) : '');
            }
        }

        return '';
    }

    /**
     * Fit all sections to the model's context window.
     */
    public function fit(): self
    {
        $model = $this->getModel();
        $contextWindow = $this->tokenizer->getContextWindow($model);
        $availableTokens = $contextWindow - $this->reservedOutputTokens;

        // Sort sections by priority (highest first)
        $sortedSections = $this->sortSectionsByPriority();

        // Calculate current token usage
        $currentTokens = $this->calculateTotalTokens($sortedSections, $model);

        // If it fits, we're done
        if ($currentTokens <= $availableTokens) {
            $this->fitted = true;

            return $this;
        }

        // Need to shrink - process sections by priority
        $this->sections = $this->shrinkSections($sortedSections, $availableTokens, $model);
        $this->fitted = true;

        return $this;
    }

    /**
     * Convert prompt to messages array (for chat models).
     *
     * @return array<array{role: string, content: string}>
     */
    public function toMessages(): array
    {
        if (! $this->fitted) {
            $this->fit();
        }

        // Sort by priority for output
        $sortedSections = $this->sortSectionsByPriority($this->sections);

        $messages = [];

        foreach ($sortedSections as $section) {
            $messages = array_merge($messages, $section->getContentAsMessages());
        }

        return $messages;
    }

    /**
     * Convert prompt to plain text.
     */
    public function toText(): string
    {
        if (! $this->fitted) {
            $this->fit();
        }

        // Sort by priority for output
        $sortedSections = $this->sortSectionsByPriority($this->sections);

        $parts = [];

        foreach ($sortedSections as $section) {
            $parts[] = $section->getContentAsString();
        }

        return implode("\n\n", $parts);
    }

    /**
     * Execute the prompt with the LLM.
     */
    public function run(array $options = []): mixed
    {
        if (! $this->llm) {
            throw new \RuntimeException('No LLM instance available. Inject LLM in constructor or use Mindwave facade.');
        }

        if (! $this->fitted) {
            $this->fit();
        }

        return $this->llm->chat($this->toMessages(), $options);
    }

    /**
     * Get the current token count.
     */
    public function getTokenCount(): int
    {
        return $this->calculateTotalTokens($this->sections, $this->getModel());
    }

    /**
     * Get the available token budget.
     */
    public function getAvailableTokens(): int
    {
        $model = $this->getModel();
        $contextWindow = $this->tokenizer->getContextWindow($model);

        return $contextWindow - $this->reservedOutputTokens;
    }

    /**
     * Check if the prompt has been fitted.
     */
    public function isFitted(): bool
    {
        return $this->fitted;
    }

    /**
     * Get all sections.
     *
     * @return array<Section>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Register a custom shrinker.
     */
    public function registerShrinker(string $name, ShrinkerInterface $shrinker): self
    {
        $this->shrinkers[$name] = $shrinker;

        return $this;
    }

    /**
     * Sort sections by priority (highest first).
     *
     * @param  array<Section>  $sections
     * @return array<Section>
     */
    private function sortSectionsByPriority(?array $sections = null): array
    {
        $sections = $sections ?? $this->sections;

        usort($sections, fn ($a, $b) => $b->priority <=> $a->priority);

        return $sections;
    }

    /**
     * Calculate total tokens for all sections.
     *
     * @param  array<Section>  $sections
     */
    private function calculateTotalTokens(array $sections, string $model): int
    {
        $total = 0;

        foreach ($sections as $section) {
            $content = $section->getContentAsString();
            $total += $this->tokenizer->count($content, $model);
        }

        return $total;
    }

    /**
     * Shrink sections to fit within available tokens.
     *
     * @param  array<Section>  $sections
     * @return array<Section>
     */
    private function shrinkSections(array $sections, int $availableTokens, string $model): array
    {
        $result = [];
        $usedTokens = 0;

        // First pass: Add all sections that can't be shrunk
        foreach ($sections as $section) {
            if (! $section->canShrink()) {
                $content = $section->getContentAsString();
                $tokens = $this->tokenizer->count($content, $model);
                $usedTokens += $tokens;
                $result[] = $section;
            }
        }

        // Check if non-shrinkable sections already exceed budget
        if ($usedTokens > $availableTokens) {
            throw new \RuntimeException(
                "Non-shrinkable sections ({$usedTokens} tokens) exceed available budget ({$availableTokens} tokens). ".
                'Increase context window or mark more sections as shrinkable.'
            );
        }

        $remainingTokens = $availableTokens - $usedTokens;

        // Second pass: Add shrinkable sections
        $shrinkableSections = array_filter($sections, fn ($s) => $s->canShrink());
        $shrinkableCount = count($shrinkableSections);

        if ($shrinkableCount === 0) {
            return $result;
        }

        // Distribute remaining tokens among shrinkable sections
        $tokensPerSection = (int) floor($remainingTokens / $shrinkableCount);

        foreach ($shrinkableSections as $section) {
            $shrinker = $this->getShrinker($section->shrinker);
            $content = $section->getContentAsString();
            $shrunkContent = $shrinker->shrink($content, $tokensPerSection, $model);

            $result[] = $section->withContent($shrunkContent);
        }

        // Resort to maintain priority order
        return $this->sortSectionsByPriority($result);
    }

    /**
     * Get a shrinker by name.
     */
    private function getShrinker(string $name): ShrinkerInterface
    {
        if (! isset($this->shrinkers[$name])) {
            throw new \InvalidArgumentException("Unknown shrinker: {$name}");
        }

        return $this->shrinkers[$name];
    }

    /**
     * Get the model, using default if not set.
     */
    private function getModel(): string
    {
        if ($this->model) {
            return $this->model;
        }

        // Try to get from LLM driver
        if ($this->llm && method_exists($this->llm, 'getModel')) {
            return $this->llm->getModel();
        }

        // Default fallback
        return 'gpt-4';
    }

    /**
     * Register default shrinkers.
     */
    private function registerDefaultShrinkers(): void
    {
        $this->shrinkers[ShrinkerType::Truncate->value] = new TruncateShrinker($this->tokenizer);
        $this->shrinkers[ShrinkerType::Compress->value] = new CompressShrinker($this->tokenizer);
    }
}
