<?php

namespace Mindwave\Mindwave\Contracts;

interface Message
{
    public function role(): string;

    public function content(): string;

    public function meta(): ?array;

    public function fromArray(array $data): ?self;

    public function toArray(): array;
}
