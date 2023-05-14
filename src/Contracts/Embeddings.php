<?php

namespace Mindwave\Mindwave\Contracts;

use Illuminate\Support\Collection;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Knowledge\Data\Knowledge;

interface Embeddings
{
    public function embed(Knowledge $knowledge): EmbeddingVector;

    /**
     * @return EmbeddingVector[]
     */
    public function embedMultiple(array|Collection $items): array;

    public function embedQuery(string $text): EmbeddingVector;
}
