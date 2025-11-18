<?php

namespace Mindwave\Mindwave\PromptComposer;

class Section
{
    public function __construct(
        public readonly string $name,
        public readonly string|array $content,
        public readonly int $priority = 50,
        public readonly ?string $shrinker = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * Create a new section.
     */
    public static function make(
        string $name,
        string|array $content,
        int $priority = 50,
        ?string $shrinker = null,
        array $metadata = [],
    ): self {
        return new self($name, $content, $priority, $shrinker, $metadata);
    }

    /**
     * Get the content as a string.
     */
    public function getContentAsString(): string
    {
        if (is_string($this->content)) {
            return $this->content;
        }

        // If it's messages array (chat format)
        if ($this->isMessagesArray()) {
            return $this->formatMessagesAsString();
        }

        // Otherwise, JSON encode
        return json_encode($this->content, JSON_PRETTY_PRINT);
    }

    /**
     * Get the content as messages array (for chat models).
     *
     * @return array<array{role: string, content: string}>
     */
    public function getContentAsMessages(): array
    {
        if (is_array($this->content) && $this->isMessagesArray()) {
            return $this->content;
        }

        // Convert string to single message
        if (is_string($this->content)) {
            return $this->stringToMessages($this->content);
        }

        // Convert arbitrary array to messages
        return $this->stringToMessages(json_encode($this->content));
    }

    /**
     * Check if content is in messages array format.
     */
    private function isMessagesArray(): bool
    {
        if (! is_array($this->content) || empty($this->content)) {
            return false;
        }

        // Check if first element has role and content
        $first = $this->content[0] ?? null;

        return is_array($first)
            && isset($first['role'])
            && isset($first['content']);
    }

    /**
     * Format messages array as a single string.
     */
    private function formatMessagesAsString(): string
    {
        $parts = [];

        foreach ($this->content as $message) {
            $role = ucfirst($message['role']);
            $content = $message['content'];
            $parts[] = "{$role}: {$content}";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Convert string to messages array based on section name.
     *
     * @return array<array{role: string, content: string}>
     */
    private function stringToMessages(string $content): array
    {
        $role = match (strtolower($this->name)) {
            'system' => 'system',
            'user', 'question', 'query' => 'user',
            'assistant', 'response' => 'assistant',
            default => 'user',
        };

        return [
            [
                'role' => $role,
                'content' => $content,
            ],
        ];
    }

    /**
     * Check if this section can be shrunk.
     */
    public function canShrink(): bool
    {
        return $this->shrinker !== null;
    }

    /**
     * Create a copy with updated content.
     */
    public function withContent(string|array $content): self
    {
        return new self(
            $this->name,
            $content,
            $this->priority,
            $this->shrinker,
            $this->metadata
        );
    }

    /**
     * Create a copy with updated metadata.
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->name,
            $this->content,
            $this->priority,
            $this->shrinker,
            [...$this->metadata, ...$metadata]
        );
    }
}
