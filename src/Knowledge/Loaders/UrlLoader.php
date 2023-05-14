<?php

namespace Mindwave\Mindwave\Knowledge\Loaders;

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Contracts\KnowledgeLoader;
use Mindwave\Mindwave\Knowledge\Data\Knowledge;
use Mindwave\Mindwave\Support\TextUtils;

class UrlLoader implements KnowledgeLoader
{
    public function load(mixed $data, array $meta = []): ?Knowledge
    {
        // TODO(14 mai 2023) ~ Helge: validate that url is valid
        // TODO(14 mai 2023) ~ Helge: Configurable agent, timeout etc
        $response = Http::get($data);

        if ($response->failed()) {
            return null;
        }

        return new Knowledge(
            content: TextUtils::cleanHtml($response->body()),
            meta: array_merge([
                'url' => $data,
                'effective_url' => (string) $response->effectiveUri(),
            ], $meta)
        );
    }
}
