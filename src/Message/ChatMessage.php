<?php

namespace Mindwave\Mindwave\Message;

use Mindwave\Mindwave\Contracts\Message;

readonly class ChatMessage implements Message
{
    public function __construct(
        protected Role $role,
        protected string $content,
        protected ?array $meta = [],
    ) {

    }

    public static function makeAiMessage(string $content, ?array $meta = []): self
    {
        return new self(Role::ai, $content, $meta);
    }

    public static function makeUserMessage(string $content, ?array $meta = []): self
    {
        return new self(Role::user, $content, $meta);
    }

    public static function makeSystemMessage(string $content, ?array $meta = []): self
    {
        return new self(Role::system, $content, $meta);
    }

    public function role(): string
    {
        return $this->role->value;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function meta(): ?array
    {
        return $this->meta;
    }

    public function fromArray(array $data): ?self
    {
        return new self(
            role: Role::tryFrom($data['role']),
            content: $data['content'],
            meta: $data['meta'],
        );
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            'meta' => $this->meta,
        ];
    }
}
