<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

interface Vectorstore
{
    public function truncate(): void;

    public function itemCount(): int;

    public function insert(VectorStoreEntry $entry): void;

    /**
     * @param  VectorStoreEntry[]  $entries
     */
    public function insertMany(array $entries): void;

    /**
     * @return  VectorStoreEntry[]
     */
    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array;
}
