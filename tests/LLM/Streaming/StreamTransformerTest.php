<?php

use Mindwave\Mindwave\LLM\Streaming\StreamTransformer;

beforeEach(function () {
    $this->simpleStream = (function () {
        yield 'hello';
        yield ' ';
        yield 'world';
    })();
});

it('maps chunks through transformation', function () {
    $stream = (function () {
        yield 'hello';
        yield 'world';
    })();

    $result = StreamTransformer::from($stream)
        ->map(fn ($chunk) => strtoupper($chunk))
        ->collect();

    expect($result)->toBe('HELLOWORLD');
});

it('filters chunks based on predicate', function () {
    $stream = (function () {
        yield 'a';
        yield '';
        yield 'b';
        yield '';
        yield 'c';
    })();

    $result = StreamTransformer::from($stream)
        ->filter(fn ($chunk) => $chunk !== '')
        ->collect();

    expect($result)->toBe('abc');
});

it('buffers chunks', function () {
    $stream = (function () {
        yield 'a';
        yield 'b';
        yield 'c';
        yield 'd';
        yield 'e';
    })();

    $chunks = StreamTransformer::from($stream)
        ->buffer(2)
        ->toArray();

    expect($chunks)->toBe(['ab', 'cd', 'e']);
});

it('debounces chunks', function () {
    $stream = (function () {
        yield 'a';
        yield 'b';
        yield 'c';
        yield 'd';
        yield 'e';
    })();

    $chunks = StreamTransformer::from($stream)
        ->debounce(2)
        ->toArray();

    expect($chunks)->toBe(['ab', 'cd', 'e']);
});

it('takes first N chunks', function () {
    $stream = (function () {
        yield 'a';
        yield 'b';
        yield 'c';
        yield 'd';
        yield 'e';
    })();

    $result = StreamTransformer::from($stream)
        ->take(3)
        ->collect();

    expect($result)->toBe('abc');
});

it('skips first N chunks', function () {
    $stream = (function () {
        yield 'a';
        yield 'b';
        yield 'c';
        yield 'd';
        yield 'e';
    })();

    $result = StreamTransformer::from($stream)
        ->skip(2)
        ->collect();

    expect($result)->toBe('cde');
});

it('executes tap without modifying stream', function () {
    $tapped = [];

    $result = StreamTransformer::from($this->simpleStream)
        ->tap(function ($chunk) use (&$tapped) {
            $tapped[] = $chunk;
        })
        ->collect();

    expect($result)->toBe('hello world');
    expect($tapped)->toBe(['hello', ' ', 'world']);
});

it('chunks by character count', function () {
    $stream = (function () {
        yield 'abc';
        yield 'defg';
        yield 'hi';
    })();

    $chunks = StreamTransformer::from($stream)
        ->chunk(3)
        ->toArray();

    expect($chunks)->toBe(['abc', 'def', 'ghi']);
});

it('reduces stream to single value', function () {
    $stream = (function () {
        yield 1;
        yield 2;
        yield 3;
        yield 4;
    })();

    $sum = StreamTransformer::from($stream)
        ->reduce(fn ($acc, $chunk) => $acc + $chunk, 0);

    expect($sum)->toBe(10);
});

it('counts chunks', function () {
    $stream = (function () {
        yield 'a';
        yield 'b';
        yield 'c';
    })();

    $count = StreamTransformer::from($stream)->count();

    expect($count)->toBe(3);
});

it('chains multiple operations', function () {
    $stream = (function () {
        yield 'hello';
        yield '';
        yield 'world';
        yield '';
        yield 'foo';
        yield 'bar';
    })();

    $result = StreamTransformer::from($stream)
        ->filter(fn ($chunk) => $chunk !== '')
        ->map(fn ($chunk) => strtoupper($chunk))
        ->buffer(2)
        ->collect();

    expect($result)->toBe('HELLOWORLDFOOBAR');
});

it('converts to array', function () {
    $chunks = StreamTransformer::from($this->simpleStream)->toArray();

    expect($chunks)->toBe(['hello', ' ', 'world']);
});

it('throws on invalid buffer size', function () {
    expect(fn () => StreamTransformer::from($this->simpleStream)->buffer(0))
        ->toThrow(\InvalidArgumentException::class, 'Buffer size must be at least 1');
});

it('throws on invalid chunk size', function () {
    expect(fn () => StreamTransformer::from($this->simpleStream)->chunk(0))
        ->toThrow(\InvalidArgumentException::class, 'Chunk size must be at least 1');
});

it('throws on negative take count', function () {
    expect(fn () => StreamTransformer::from($this->simpleStream)->take(-1))
        ->toThrow(\InvalidArgumentException::class, 'Take count must be non-negative');
});

it('throws on negative skip count', function () {
    expect(fn () => StreamTransformer::from($this->simpleStream)->skip(-1))
        ->toThrow(\InvalidArgumentException::class, 'Skip count must be non-negative');
});

it('handles empty stream', function () {
    $stream = (function () {
        if (false) {
            yield 'never';
        }
    })();

    $result = StreamTransformer::from($stream)->collect();

    expect($result)->toBe('');
});

it('handles unicode characters in chunk', function () {
    $stream = (function () {
        yield 'Hello 世界';
        yield ' 🌍';
    })();

    $chunks = StreamTransformer::from($stream)
        ->chunk(6)
        ->toArray();

    expect(count($chunks))->toBeGreaterThan(0);
});
