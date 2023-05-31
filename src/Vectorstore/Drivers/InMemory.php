<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
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
        $id = Str::uuid()->toString();
        $this->items[$id] = [
            'vector' => $entry->vector->toArray(),
            'score' => $entry->score,
            'metadata' => $entry->meta(),
        ];
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
            ->map(function ($item) use ($embedding) {
                return new VectorStoreEntry(
                    vector: $vector = new EmbeddingVector($item['vector']),
                    document: new Document(
                        content: $item['metadata']['_mindwave_doc_content'],
                        metadata: array_merge(json_decode($item['metadata']['_mindwave_doc_metadata'], true), [
                            '_mindwave_doc_source_id' => $item['metadata']['_mindwave_doc_source_id'],
                            '_mindwave_doc_source_type' => $item['metadata']['_mindwave_doc_source_type'],
                            '_mindwave_doc_chunk_index' => $item['metadata']['_mindwave_doc_chunk_index'],
                        ])
                    ),
                    score: Similarity::cosine($vector, $embedding)
                );
            })
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
