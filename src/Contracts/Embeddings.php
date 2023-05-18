<?php

namespace Mindwave\Mindwave\Contracts;

use Illuminate\Support\Collection;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;

interface Embeddings
{
    public function embed(Document $document): EmbeddingVector;

    /**
     * @return EmbeddingVector[]
     */
    public function embedMultiple(array|Collection $items): array;

    public function embedQuery(string $text): EmbeddingVector;
}
