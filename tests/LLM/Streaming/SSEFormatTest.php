<?php

use Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse;

/**
 * Capture all output from a StreamedResponse, including flushed content.
 * The StreamedTextResponse calls ob_flush() internally, so a simple
 * ob_start/ob_get_clean won't work — we need a callback-based buffer.
 */
function captureStreamOutput(\Symfony\Component\HttpFoundation\StreamedResponse $httpResponse): string
{
    $output = '';
    ob_start(function (string $data) use (&$output) {
        $output .= $data;

        return '';
    });
    $httpResponse->sendContent();
    ob_end_clean();

    return $output;
}

it('formats SSE output with correct event and data lines', function () {
    $stream = (function () {
        yield 'Hello';
        yield 'World';
    })();

    $response = new StreamedTextResponse($stream);
    $output = captureStreamOutput($response->toStreamedResponse());

    expect($output)->toContain("event: message\ndata: Hello\n\n");
    expect($output)->toContain("event: message\ndata: World\n\n");
    expect($output)->toContain("event: done\n");
});

it('sends done event with complete status after all chunks', function () {
    $stream = (function () {
        yield 'only chunk';
    })();

    $response = new StreamedTextResponse($stream);
    $output = captureStreamOutput($response->toStreamedResponse());

    $doneData = json_decode(
        explode("\n\n", explode("event: done\ndata: ", $output)[1])[0],
        true
    );

    expect($doneData)->toBe(['status' => 'complete']);
});

it('escapes newlines in chunk data', function () {
    $stream = (function () {
        yield "line1\nline2";
    })();

    $response = new StreamedTextResponse($stream);
    $output = captureStreamOutput($response->toStreamedResponse());

    // Newlines in data should be escaped so SSE protocol isn't broken
    expect($output)->toContain('data: line1\nline2');
});

it('sends error event when stream throws without handler', function () {
    $stream = (function () {
        yield 'before error';
        throw new \RuntimeException('Stream broke');
    })();

    $response = new StreamedTextResponse($stream);
    $output = captureStreamOutput($response->toStreamedResponse());

    expect($output)->toContain('event: error');
    expect($output)->toContain('Stream broke');
});

it('sends error event with retry info when handler returns true', function () {
    $stream = (function () {
        yield 'before';
        throw new \RuntimeException('Retryable error');
    })();

    $response = new StreamedTextResponse($stream);
    $response->onError(fn () => true);
    $output = captureStreamOutput($response->toStreamedResponse());

    expect($output)->toContain('event: message');
    expect($output)->toContain('event: error');

    $errorLine = array_filter(
        explode("\n\n", $output),
        fn ($block) => str_contains($block, 'event: error')
    );
    $errorBlock = array_values($errorLine)[0];
    $errorData = json_decode(explode('data: ', $errorBlock)[1], true);

    expect($errorData['retry'])->toBeTrue();
    expect($errorData['attempt'])->toBe(1);
});

it('sends cancelled event when stream is cancelled', function () {
    $stream = (function () {
        yield 'first';
        yield 'second';
        yield 'third';
    })();

    $response = new StreamedTextResponse($stream);
    $response->onChunk(function () use ($response) {
        $response->cancel();
    });

    $output = captureStreamOutput($response->toStreamedResponse());

    expect($output)->toContain('event: cancelled');
    expect($output)->not->toContain('event: done');
});

it('calls completion handler on successful stream', function () {
    $stream = (function () {
        yield 'done';
    })();

    $completed = false;
    $response = new StreamedTextResponse($stream);
    $response->onComplete(function () use (&$completed) {
        $completed = true;
    });

    captureStreamOutput($response->toStreamedResponse());

    expect($completed)->toBeTrue();
});

it('does not call completion handler when cancelled', function () {
    $stream = (function () {
        yield 'chunk';
    })();

    $completed = false;
    $response = new StreamedTextResponse($stream);
    $response->onComplete(function () use (&$completed) {
        $completed = true;
    });
    $response->onChunk(function () use ($response) {
        $response->cancel();
    });

    captureStreamOutput($response->toStreamedResponse());

    expect($completed)->toBeFalse();
});

it('plain response outputs raw text without SSE framing', function () {
    $stream = (function () {
        yield 'Hello';
        yield ' World';
    })();

    $response = new StreamedTextResponse($stream);
    $output = captureStreamOutput($response->toPlainStreamedResponse());

    expect($output)->toBe('Hello World');
    expect($output)->not->toContain('event:');
    expect($output)->not->toContain('data:');
});

it('sets correct SSE headers', function () {
    $stream = (function () {
        yield 'x';
    })();

    $response = new StreamedTextResponse($stream);
    $httpResponse = $response->toStreamedResponse();

    expect($httpResponse->headers->get('Content-Type'))->toBe('text/event-stream');
    expect($httpResponse->headers->get('Cache-Control'))->toContain('no-cache');
    expect($httpResponse->headers->get('Connection'))->toBe('keep-alive');
    expect($httpResponse->headers->get('X-Accel-Buffering'))->toBe('no');
});

it('uses named SSE events so EventSource.addEventListener works without a custom parser', function () {
    // Laravel AI SDK streams as: data: {"type":"text_delta","delta":"Hello"}\n\n
    // — no "event:" field, so everything hits onmessage and consumers need to
    //   JSON-decode every line and switch on "type" themselves.
    //
    // Mindwave uses proper named events (event: message / event: done / event: error)
    // so the browser EventSource API can dispatch them directly:
    //   eventSource.addEventListener('message', ...)
    //   eventSource.addEventListener('done', ...)
    //   eventSource.addEventListener('error', ...)
    $stream = (function () {
        yield 'Hello ';
        yield 'World';
        yield "with\nnewline";
    })();

    $response = new StreamedTextResponse($stream);
    $output = captureStreamOutput($response->toStreamedResponse());

    // Parse SSE the same way a browser EventSource would:
    // split on double-newline, extract event: and data: fields.
    $blocks = array_filter(explode("\n\n", $output), fn ($b) => $b !== '');

    $events = array_values(array_map(function (string $block) {
        $event = null;
        $data = null;
        foreach (explode("\n", $block) as $line) {
            if (str_starts_with($line, 'event: ')) {
                $event = substr($line, 7);
            } elseif (str_starts_with($line, 'data: ')) {
                $data = substr($line, 6);
            }
        }

        return ['event' => $event, 'data' => $data];
    }, $blocks));

    // Three message events + one done event
    expect($events)->toHaveCount(4);

    // Data is plain text, not JSON — no decoding needed for text chunks
    expect($events[0])->toBe(['event' => 'message', 'data' => 'Hello ']);
    expect($events[1])->toBe(['event' => 'message', 'data' => 'World']);
    expect($events[2])->toBe(['event' => 'message', 'data' => 'with\nnewline']);

    // Only the done event carries JSON
    expect($events[3]['event'])->toBe('done');
    expect(json_decode($events[3]['data'], true))->toBe(['status' => 'complete']);
});

it('handles empty stream gracefully', function () {
    $stream = (function () {
        return;
        yield; // never reached, makes it a generator
    })();

    $response = new StreamedTextResponse($stream);
    $output = captureStreamOutput($response->toStreamedResponse());

    // Should still send done event even with no chunks
    expect($output)->toContain('event: done');
    expect($output)->not->toContain('event: message');
});
