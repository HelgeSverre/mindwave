<?php

namespace Mindwave\Mindwave\Knowledge\Loaders;

use Mindwave\Mindwave\Contracts\KnowledgeLoader;
use Mindwave\Mindwave\Knowledge\Data\Knowledge;
use Mindwave\Mindwave\Support\TextUtils;

class HtmlLoader implements KnowledgeLoader
{
    public function load(mixed $data, array $meta = []): ?Knowledge
    {
        return new Knowledge(
            content: TextUtils::cleanHtml($data),
            meta: $meta
        );
    }
}
