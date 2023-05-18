<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Probots\Pinecone\Client as PineconeClient;

class Pinecone implements Vectorstore
{
    protected PineconeClient $client;

    public function __construct(PineconeClient $client)
    {
        $this->client = $client;
    }

    public function fetchById(string $id): ?VectorStoreEntry
    {
        // TODO: Implement fetchById() method.
    }

    public function fetchByIds(array $ids): array
    {
        // TODO: Implement fetchByIds() method.
    }

    public function insertVector(VectorStoreEntry $entry): void
    {
        // TODO: Implement insertVector() method.
    }

    public function upsertVector(VectorStoreEntry $entry): void
    {
        // TODO: Implement upsertVector() method.
    }

    public function insertVectors(array $entries): void
    {
        // TODO: Implement insertVectors() method.
    }

    public function upsertVectors(array $entries): void
    {
        // TODO: Implement upsertVectors() method.
    }

    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array
    {
        // TODO: Implement similaritySearchByVector() method.
    }
}
