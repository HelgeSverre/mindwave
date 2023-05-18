<?php

namespace Mindwave\Mindwave\Document\Loaders;

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Contracts\DocumentLoader;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Support\TextUtils;

class UrlLoader implements DocumentLoader
{
    public function load(mixed $data, array $meta = []): ?Document
    {
        // TODO(14 mai 2023) ~ Helge: validate that url is valid
        // TODO(14 mai 2023) ~ Helge: Configurable agent, timeout etc
        $response = Http::get($data);

        if ($response->failed()) {
            return null;
        }

        return new Document(
            content: TextUtils::cleanHtml($response->body()),
            meta: array_merge([
                'url' => $data,
                'effective_url' => (string) $response->effectiveUri(),
            ], $meta)
        );
    }
}
