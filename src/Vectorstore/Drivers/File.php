<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Support\Similarity;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

class File implements Vectorstore
{
    protected string $path;

    /**
     * @var array<string, VectorStoreEntry>
     */
    protected array $items = [];

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->loadFromFile();
    }

    protected function loadFromFile(): void
    {
        if (file_exists($this->path)) {
            $data = json_decode(file_get_contents($this->path), true);
            $this->items = [];

            foreach ($data as $id => $item) {
                $this->items[$id] = new VectorStoreEntry(
                    id: $id,
                    vector: new EmbeddingVector($item['vector']),
                    metadata: $item['metadata'] ?? null
                );
            }
        } else {
            $this->items = [];
        }
    }

    // TODO(20 mai 2023) ~ Helge: do this in destructor?
    protected function saveToFile(): void
    {
        $data = [];

        foreach ($this->items as $id => $entry) {
            /** @var VectorStoreEntry $entry */
            $data[$id] = [
                'id' => $entry->id,
                'vector' => $entry->vector->toArray(),
                'score' => $entry->score,
                'metadata' => $entry->metadata,
            ];
        }

        $directory = dirname($this->path);

        if (! file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->path, json_encode($data));
    }

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
        $this->saveToFile();
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
            ->map(function (VectorStoreEntry $entry) use ($embedding) {

                return $entry->cloneWithScore(
                    score: Similarity::cosine($entry->vector, $embedding)
                );
            })
            ->sortByDesc(fn (VectorStoreEntry $entry) => $entry->score, SORT_NUMERIC)
            ->take($count)
            ->values()
            ->all();
    }
}
