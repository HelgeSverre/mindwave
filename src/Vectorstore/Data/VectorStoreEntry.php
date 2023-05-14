<?php

namespace Mindwave\Mindwave\Vectorstore\Data;

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;

class VectorStoreEntry
{
    public readonly string $id;

    public readonly EmbeddingVector $vector;

    public readonly array $metadata;

    public readonly ?float $similarityScore;

    public function __construct(string $id, EmbeddingVector $vector, array $metadata, ?float $similarityScore = null)
    {
        $this->id = $id;
        $this->vector = $vector;
        $this->metadata = $metadata;
        $this->similarityScore = $similarityScore;
    }
}
