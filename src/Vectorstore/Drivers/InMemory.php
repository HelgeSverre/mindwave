<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Support\Similarity;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

class InMemory implements Vectorstore
{
    /**
     * @var array<string, VectorStoreEntry>
     */
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
            ->values()
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

    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array
    {
        return collect($this->items)
            ->map(fn (VectorStoreEntry $entry) => $entry->cloneWithScore(
                score: Similarity::cosine($entry->vector, $embedding)
            ))
            ->sortByDesc(fn (VectorStoreEntry $entry) => $entry->score, SORT_NUMERIC)
            ->take($count)
            ->values()
            ->all();
    }
}
