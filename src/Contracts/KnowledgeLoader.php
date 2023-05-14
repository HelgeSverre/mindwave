<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\Knowledge\Data\Knowledge;

interface KnowledgeLoader
{
    public function load(mixed $data, array $meta = []): ?Knowledge;
}
