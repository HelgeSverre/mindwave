<?php

namespace Mindwave\Mindwave\Embeddings\Data;

use ArrayAccess;
use RuntimeException;

class EmbeddingVector implements ArrayAccess
{
    public readonly array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->values[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new RuntimeException('Cannot modify a read-only EmbeddingVector.');
    }

    public function offsetUnset($offset): void
    {
        throw new RuntimeException('Cannot modify a read-only EmbeddingVector.');
    }

    public function __toArray(): array
    {
        return $this->values;
    }
}
