<?php

namespace Mindwave\Mindwave\Document\Loaders;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mindwave\Mindwave\Contracts\DocumentLoader;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Support\TextUtils;

class WebLoader implements DocumentLoader
{
    public function load(mixed $data, array $meta = []): ?Document
    {
        // Validate URL format
        if (! filter_var($data, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL provided: {$data}");
        }

        $timeout = config('mindwave-context.http_timeout', 30);

        $response = Http::timeout($timeout)->get($data);

        if ($response->failed()) {
            return null;
        }

        return new Document(
            content: TextUtils::cleanHtml($response->body()),
            metadata: array_merge([
                'url' => $data,
                'effective_url' => (string) $response->effectiveUri(),
            ], $meta)
        );
    }
}
