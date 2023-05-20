<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

interface Vectorstore
{
    public function fetchById(string $id): ?VectorStoreEntry;

    /**
     * @return  VectorStoreEntry[]
     */
    public function fetchByIds(array $ids): array;

    public function insertVector(VectorStoreEntry $entry): void;

    public function upsertVector(VectorStoreEntry $entry): void;

    /**
     * @param  VectorStoreEntry[]  $entries
     */
    public function insertVectors(array $entries): void;

    /**
     * @param  VectorStoreEntry[]  $entries
     */
    public function upsertVectors(array $entries): void;

    /**
     * @return  VectorStoreEntry[]
     */
    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array;

    // TODO(14 mai 2023) ~ Helge: Wait with this one
    // public function maxMarginalRelevanceSearchByVector(EmbeddingVector $embedding, int $k = 4, int $fetch_k = 20, float $lambda_mult = 0.5, array $metadata = []): array;
}
