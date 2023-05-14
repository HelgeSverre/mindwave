<?php

namespace Mindwave\Mindwave\Contracts;

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Knowledge\Data\Knowledge;

interface Vectorstore
{
    public function addKnowledge(Knowledge $knowledge, array $extra = []): array;

    public function search(string $query, string $search_type, array $extra = []): array;

    public function similaritySearchByVector(EmbeddingVector $embedding, int $k = 4, array $extra = []): array;

    public function maxMarginalRelevanceSearchByVector(EmbeddingVector $embedding, int $k = 4, int $fetch_k = 20, float $lambda_mult = 0.5, array $extra = []): array;
}
