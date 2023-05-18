<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\Document\Data\Document;

interface DocumentLoader
{
    public function load(mixed $data, array $meta = []): ?Document;
}
