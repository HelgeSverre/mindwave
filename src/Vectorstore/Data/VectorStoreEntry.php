<?php

namespace Mindwave\Mindwave\Vectorstore\Data;

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;

class VectorStoreEntry
{
    public readonly string $id;

    public readonly EmbeddingVector $vector;

    public readonly array $metadata;

    public readonly ?float $score;

    public function __construct(string $id, EmbeddingVector $vector, array $metadata = [], ?float $score = null)
    {
        $this->id = $id;
        $this->vector = $vector;
        $this->metadata = $metadata;
        $this->score = $score;
    }

    public function cloneWithScore(float $score): self
    {
        return new self(
            id: $this->id,
            vector: $this->vector,
            metadata: $this->metadata,
            score: $score,
        );
    }
}
