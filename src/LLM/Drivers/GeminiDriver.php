<?php

namespace Mindwave\Mindwave\LLM\Drivers;

use Generator;
use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Exceptions\StreamingException;
use Mindwave\Mindwave\LLM\Responses\ChatResponse;
use Mindwave\Mindwave\LLM\Responses\StreamChunk;
use Throwable;

class GeminiDriver extends BaseDriver implements LLM
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(
        protected string $apiKey,
        protected string $model = 'gemini-2.0-flash',
        protected ?string $systemMessage = null,
        protected int $maxTokens = 1000,
        protected float $temperature = 0.7,
    ) {}

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function generateText(string $prompt): ?string
    {
        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        return $response->content;
    }

    public function chat(array $messages, array $options = []): ChatResponse
    {
        $body = $this->buildRequestBody($messages);
        $url = self::BASE_URL."/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::post($url, $body)->throw()->json();

        return $this->parseResponse($response);
    }

    public function streamText(string $prompt): Generator
    {
        try {
            $messages = [['role' => 'user', 'content' => $prompt]];
            $body = $this->buildRequestBody($messages);
            $url = self::BASE_URL."/models/{$this->model}:streamGenerateContent?alt=sse&key={$this->apiKey}";

            $response = Http::withOptions(['stream' => true])
                ->post($url, $body);

            if ($response->failed()) {
                throw new \RuntimeException("Gemini API error: {$response->status()}");
            }

            foreach ($this->parseSseStream($response->toPsrResponse()->getBody()) as $chunk) {
                $text = $this->extractTextFromChunk($chunk);
                if ($text !== '') {
                    yield $text;
                }
            }
        } catch (Throwable $e) {
            if ($e instanceof StreamingException) {
                throw $e;
            }
            throw StreamingException::connectionFailed('gemini', $this->model, $e);
        }
    }

    public function streamChat(array $messages, array $options = []): Generator
    {
        try {
            $body = $this->buildRequestBody($messages);
            $url = self::BASE_URL."/models/{$this->model}:streamGenerateContent?alt=sse&key={$this->apiKey}";

            $response = Http::withOptions(['stream' => true])
                ->post($url, $body);

            if ($response->failed()) {
                throw new \RuntimeException("Gemini API error: {$response->status()}");
            }

            foreach ($this->parseSseStream($response->toPsrResponse()->getBody()) as $chunk) {
                yield $this->mapToStreamChunk($chunk);
            }
        } catch (Throwable $e) {
            if ($e instanceof StreamingException) {
                throw $e;
            }
            throw StreamingException::connectionFailed('gemini', $this->model, $e);
        }
    }

    private function buildRequestBody(array $messages): array
    {
        $contents = [];

        foreach ($messages as $message) {
            $role = match ($message['role']) {
                'assistant' => 'model',
                'system' => 'user', // Gemini handles system via systemInstruction
                default => 'user',
            };

            // Skip system messages — they go into systemInstruction
            if ($message['role'] === 'system') {
                continue;
            }

            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $message['content']]],
            ];
        }

        $body = ['contents' => $contents];

        // System instruction
        $systemText = $this->systemMessage;
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemText = $message['content'];
                break;
            }
        }
        if ($systemText) {
            $body['systemInstruction'] = ['parts' => [['text' => $systemText]]];
        }

        $body['generationConfig'] = [
            'maxOutputTokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];

        return $body;
    }

    private function parseResponse(array $response): ChatResponse
    {
        $candidates = $response['candidates'] ?? [];
        $candidate = $candidates[0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];

        $text = '';
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        $usage = $response['usageMetadata'] ?? [];

        return new ChatResponse(
            content: $text,
            role: 'assistant',
            inputTokens: $usage['promptTokenCount'] ?? null,
            outputTokens: $usage['candidatesTokenCount'] ?? null,
            finishReason: $candidate['finishReason'] ?? null,
            model: $this->model,
            raw: $response,
        );
    }

    /**
     * Parse SSE stream from Gemini's streaming endpoint.
     *
     * @return Generator<array> Yields parsed JSON chunks
     */
    private function parseSseStream($body): Generator
    {
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(8192);

            // Split on double newlines (SSE event boundaries)
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                // Extract data from SSE event
                foreach (explode("\n", $event) as $line) {
                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);
                        $chunk = json_decode($json, true);
                        if (is_array($chunk)) {
                            yield $chunk;
                        }
                    }
                }
            }
        }

        // Handle remaining buffer
        if (trim($buffer) !== '') {
            foreach (explode("\n", $buffer) as $line) {
                if (str_starts_with($line, 'data: ')) {
                    $json = substr($line, 6);
                    $chunk = json_decode($json, true);
                    if (is_array($chunk)) {
                        yield $chunk;
                    }
                }
            }
        }
    }

    private function extractTextFromChunk(array $chunk): string
    {
        $candidates = $chunk['candidates'] ?? [];
        $candidate = $candidates[0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];

        $text = '';
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        return $text;
    }

    private function mapToStreamChunk(array $chunk): StreamChunk
    {
        $candidates = $chunk['candidates'] ?? [];
        $candidate = $candidates[0] ?? [];
        $usage = $chunk['usageMetadata'] ?? [];

        return new StreamChunk(
            content: $this->extractTextFromChunk($chunk) ?: null,
            role: 'assistant',
            finishReason: $candidate['finishReason'] ?? null,
            model: $this->model,
            inputTokens: $usage['promptTokenCount'] ?? null,
            outputTokens: $usage['candidatesTokenCount'] ?? null,
            raw: $chunk,
        );
    }
}
