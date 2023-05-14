<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

class InMemory implements Vectorstore
{
    protected array $items = [];

    public function fetchById(string $id): ?VectorStoreEntry
    {
        return $this->items[$id] ?? null;
    }

    public function fetchByIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => $this->fetchById($id))
            ->filter()
            ->all();
    }

    public function insertVector(VectorStoreEntry $entry): void
    {
        $this->items[$entry->id] = clone $entry;
    }

    public function upsertVector(VectorStoreEntry $entry): void
    {
        $this->insertVector($entry);
    }

    public function insertVectors(array $entries): void
    {
        foreach ($entries as $entry) {
            $this->insertVector($entry);
        }
    }

    public function upsertVectors(array $entries): void
    {
        $this->insertVectors($entries);
    }

    public function similaritySearchByVector(EmbeddingVector $embedding, int $k = 4, array $meta = []): array
    {
        // TODO: Implement similaritySearchByVector() method.
    }
}
