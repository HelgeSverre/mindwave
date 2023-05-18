<?php

namespace Mindwave\Mindwave\Document\Loaders;

use Mindwave\Mindwave\Contracts\DocumentLoader;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Support\TextUtils;

class HtmlLoader implements DocumentLoader
{
    public function load(mixed $data, array $meta = []): ?Document
    {
        // TODO(14 mai 2023) ~ Helge: Allow elements to remove and whitespace normalization to be configured in config file.

        return new Document(
            content: TextUtils::cleanHtml($data),
            meta: $meta
        );
    }
}
