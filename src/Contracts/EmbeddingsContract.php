<?php

namespace Mindwave\Mindwave\Contracts;

use Illuminate\Support\Collection;

interface EmbeddingsContract
{
    public function embedKnowledge(array|Collection $items): array;

    public function embedQuery(string $text): array;
}
