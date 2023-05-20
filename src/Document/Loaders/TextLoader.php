<?php

namespace Mindwave\Mindwave\Document\Loaders;

use Mindwave\Mindwave\Contracts\DocumentLoader;
use Mindwave\Mindwave\Document\Data\Document;

class TextLoader implements DocumentLoader
{
    public function load(mixed $data, array $meta = []): ?Document
    {
        return new Document(
            content: $data,
            metadata: $meta
        );
    }
}
