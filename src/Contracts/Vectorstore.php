<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

interface Vectorstore
{
    // todo: do we need this?
    public function fetchById(string $id): ?VectorStoreEntry;

    /**
     * @return  VectorStoreEntry[]
     */
    // todo: do we need this?
    public function fetchByIds(array $ids): array;

    public function insertVector(VectorStoreEntry $entry): void;

    // TODO(22 May 2023) ~ Helge: REMOVE
    public function upsertVector(VectorStoreEntry $entry): void;

    /**
     * @param  VectorStoreEntry[]  $entries
     */
    public function insertVectors(array $entries): void;

    // TODO(22 May 2023) ~ Helge: REMOVE

    /**
     * @param  VectorStoreEntry[]  $entries
     */
    public function upsertVectors(array $entries): void;

    /**
     * @return  VectorStoreEntry[]
     */
    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array;

    // TODO(14 mai 2023) ~ Helge: implement
    // public function maxMarginalRelevanceSearchByVector(EmbeddingVector $embedding, int $k = 4, int $fetch_k = 20, float $lambda_mult = 0.5, array $metadata = []): array;
}
