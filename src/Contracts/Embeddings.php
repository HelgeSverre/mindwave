<?php

namespace Mindwave\Mindwave\Contracts;

use Illuminate\Support\Collection;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Knowledge\Data\Document;

interface Embeddings
{
    public function embed(Document $knowledge): EmbeddingVector;

    /**
     * @return EmbeddingVector[]
     */
    public function embedMultiple(array|Collection $items): array;

    public function embedQuery(string $text): EmbeddingVector;
}
