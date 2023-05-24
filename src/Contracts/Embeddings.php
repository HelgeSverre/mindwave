<?php

namespace Mindwave\Mindwave\Contracts;

use Illuminate\Support\Collection;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;

interface Embeddings
{
    public function embedText(string $text): EmbeddingVector;

    /**
     * @return EmbeddingVector[]
     */
    public function embedTexts(array $texts): array;

    public function embedDocument(Document $document): EmbeddingVector;

    /**
     * @return EmbeddingVector[]
     */
    public function embedDocuments(array|Collection $items): array;
}
