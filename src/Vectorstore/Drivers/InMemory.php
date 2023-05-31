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

    public function insert(VectorStoreEntry $entry): void
    {
        $this->items[] = clone $entry;
    }

    public function upsertVector(VectorStoreEntry $entry): void
    {
        $this->insert($entry);
    }

    public function insertMany(array $entries): void
    {
        foreach ($entries as $entry) {
            $this->insert($entry);
        }
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

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function truncate(): void
    {
        $this->items = [];
    }
}
