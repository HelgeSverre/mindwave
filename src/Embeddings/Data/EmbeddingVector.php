<?php

namespace Mindwave\Mindwave\Embeddings\Data;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use RuntimeException;
use Traversable;

class EmbeddingVector implements ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, \JsonSerializable
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

    public function toArray()
    {
        return $this->__toArray();
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->values, $options);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toJson();
    }
}
