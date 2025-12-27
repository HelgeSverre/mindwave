<?php

namespace Mindwave\Mindwave\LLM\Streaming;

use Generator;

/**
 * Stream Transformer
 *
 * Provides functional programming utilities for transforming streams.
 * Supports map, filter, buffer, debounce, and other common operations.
 *
 * All operations are lazy and only execute when the stream is consumed.
 *
 * Usage:
 * ```php
 * $stream = $llm->streamText($prompt);
 *
 * $transformed = StreamTransformer::from($stream)
 *     ->filter(fn($chunk) => strlen($chunk) > 0)
 *     ->map(fn($chunk) => strtoupper($chunk))
 *     ->buffer(5)
 *     ->getGenerator();
 *
 * foreach ($transformed as $chunk) {
 *     echo $chunk;
 * }
 * ```
 */
class StreamTransformer
{
    private Generator $stream;

    /**
     * @param  Generator<string>  $stream  The input stream
     */
    public function __construct(Generator $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Create a transformer from a generator.
     *
     * @param  Generator<string>  $stream  The input stream
     */
    public static function from(Generator $stream): self
    {
        return new self($stream);
    }

    /**
     * Map each chunk through a transformation function.
     *
     * @param  callable(string): string  $callback  The transformation function
     */
    public function map(callable $callback): self
    {
        $originalStream = $this->stream;

        $this->stream = (function () use ($originalStream, $callback) {
            foreach ($originalStream as $chunk) {
                yield $callback($chunk);
            }
        })();

        return $this;
    }

    /**
     * Filter chunks based on a predicate function.
     *
     * @param  callable(string): bool  $predicate  The filter predicate
     */
    public function filter(callable $predicate): self
    {
        $originalStream = $this->stream;

        $this->stream = (function () use ($originalStream, $predicate) {
            foreach ($originalStream as $chunk) {
                if ($predicate($chunk)) {
                    yield $chunk;
                }
            }
        })();

        return $this;
    }

    /**
     * Buffer chunks and emit them in batches.
     *
     * @param  int  $size  Number of chunks to buffer before emitting
     */
    public function buffer(int $size): self
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Buffer size must be at least 1');
        }

        $originalStream = $this->stream;

        $this->stream = (function () use ($originalStream, $size) {
            $buffer = [];

            foreach ($originalStream as $chunk) {
                $buffer[] = $chunk;

                if (count($buffer) >= $size) {
                    yield implode('', $buffer);
                    $buffer = [];
                }
            }

            // Emit remaining buffered chunks
            if (count($buffer) > 0) {
                yield implode('', $buffer);
            }
        })();

        return $this;
    }

    /**
     * Debounce chunks to reduce the emission rate.
     *
     * Only emits after a specified time window has passed without new chunks.
     * Note: This uses a simple counter-based debouncing, not time-based.
     *
     * @param  int  $count  Number of chunks to skip before emitting
     */
    public function debounce(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Debounce count must be non-negative');
        }

        $originalStream = $this->stream;

        $this->stream = (function () use ($originalStream, $count) {
            $counter = 0;
            $accumulated = '';

            foreach ($originalStream as $chunk) {
                $accumulated .= $chunk;
                $counter++;

                if ($counter >= $count) {
                    yield $accumulated;
                    $accumulated = '';
                    $counter = 0;
                }
            }

            // Emit remaining accumulated content
            if ($accumulated !== '') {
                yield $accumulated;
            }
        })();

        return $this;
    }

    /**
     * Take only the first N chunks from the stream.
     *
     * @param  int  $count  Number of chunks to take
     */
    public function take(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Take count must be non-negative');
        }

        $originalStream = $this->stream;

        $this->stream = (function () use ($originalStream, $count) {
            $taken = 0;

            foreach ($originalStream as $chunk) {
                if ($taken >= $count) {
                    break;
                }

                yield $chunk;
                $taken++;
            }
        })();

        return $this;
    }

    /**
     * Skip the first N chunks from the stream.
     *
     * @param  int  $count  Number of chunks to skip
     */
    public function skip(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Skip count must be non-negative');
        }

        $originalStream = $this->stream;

        $this->stream = (function () use ($originalStream, $count) {
            $skipped = 0;

            foreach ($originalStream as $chunk) {
                if ($skipped < $count) {
                    $skipped++;

                    continue;
                }

                yield $chunk;
            }
        })();

        return $this;
    }

    /**
     * Execute a callback for each chunk without modifying the stream.
     *
     * Useful for side effects like logging or progress tracking.
     *
     * @param  callable(string): void  $callback  The side effect callback
     */
    public function tap(callable $callback): self
    {
        $originalStream = $this->stream;

        $this->stream = (function () use ($originalStream, $callback) {
            foreach ($originalStream as $chunk) {
                $callback($chunk);
                yield $chunk;
            }
        })();

        return $this;
    }

    /**
     * Merge multiple streams together.
     *
     * Note: This interleaves the streams, not concatenates them.
     *
     * @param  Generator<string>  ...$streams  Additional streams to merge
     */
    public function merge(Generator ...$streams): self
    {
        $originalStream = $this->stream;
        $allStreams = [$originalStream, ...$streams];

        $this->stream = (function () use ($allStreams) {
            $activeStreams = $allStreams;

            while (count($activeStreams) > 0) {
                foreach ($activeStreams as $key => $stream) {
                    if ($stream->valid()) {
                        yield $stream->current();
                        $stream->next();
                    } else {
                        unset($activeStreams[$key]);
                    }
                }
            }
        })();

        return $this;
    }

    /**
     * Chunk the stream into fixed-size pieces.
     *
     * Unlike buffer(), this operates on character count, not chunk count.
     *
     * @param  int  $size  Number of characters per chunk
     */
    public function chunk(int $size): self
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Chunk size must be at least 1');
        }

        $originalStream = $this->stream;

        $this->stream = (function () use ($originalStream, $size) {
            $buffer = '';

            foreach ($originalStream as $chunk) {
                $buffer .= $chunk;

                while (mb_strlen($buffer) >= $size) {
                    yield mb_substr($buffer, 0, $size);
                    $buffer = mb_substr($buffer, $size);
                }
            }

            // Emit remaining buffer
            if ($buffer !== '') {
                yield $buffer;
            }
        })();

        return $this;
    }

    /**
     * Reduce the stream to a single value.
     *
     * This is a terminal operation that consumes the stream.
     *
     * @param  callable(mixed, string): mixed  $callback  The reduction function
     * @param  mixed  $initial  The initial accumulator value
     * @return mixed The final reduced value
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $accumulator = $initial;

        foreach ($this->stream as $chunk) {
            $accumulator = $callback($accumulator, $chunk);
        }

        return $accumulator;
    }

    /**
     * Collect all chunks into a single string.
     *
     * This is a terminal operation that consumes the stream.
     */
    public function collect(): string
    {
        return $this->reduce(fn ($acc, $chunk) => $acc.$chunk, '');
    }

    /**
     * Count the number of chunks in the stream.
     *
     * This is a terminal operation that consumes the stream.
     */
    public function count(): int
    {
        return $this->reduce(fn ($acc, $chunk) => $acc + 1, 0);
    }

    /**
     * Get the underlying generator.
     *
     * @return Generator<string> The transformed stream
     */
    public function getGenerator(): Generator
    {
        return $this->stream;
    }

    /**
     * Convert the stream to an array.
     *
     * This is a terminal operation that consumes the stream.
     *
     * @return array<string> All chunks as an array
     */
    public function toArray(): array
    {
        return iterator_to_array($this->stream);
    }
}
