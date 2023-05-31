<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Probots\Pinecone\Client as PineconeClient;

class Pinecone implements Vectorstore
{
    protected PineconeClient $client;

    private string $index;

    public function __construct(PineconeClient $client, string $index)
    {
        $this->client = $client;
        $this->index = $index;
    }

    public function insert(VectorStoreEntry $entry): void
    {
        $id = Str::uuid();

        $vectors = array_filter([
            'id' => $id,
            'values' => $entry->vector->values,
            // Pinecone does not allow "null" values, so we have to filter them out
            'metadata' => array_filter($entry->meta()),
        ]);

        $this->client->index($this->index)->vectors()->upsert($vectors);
    }

    public function insertMany(array $entries): void
    {
        $vectors = collect($entries)->map(fn (VectorStoreEntry $entry) => [
            'id' => Str::uuid()->toString(),
            'values' => $entry->vector->values,
            // Pinecone does not allow "null" values, so we have to filter them out
            'metadata' => array_filter($entry->meta()),
        ])->toArray();

        $this->client->index($this->index)->vectors()->upsert($vectors);
    }

    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array
    {
        return $this->client->index($this->index)->vectors()->query(
            vector: $embedding->values,
            topK: $count,
            includeMetadata: true,
            includeVector: true,
        )->collect('matches')->map(function ($match) {

            $meta = json_decode($match['metadata']['_mindwave_doc_metadata'] ?? '[]', true);

            return new VectorStoreEntry(
                vector: new EmbeddingVector($match['values']),
                document: new Document(
                    content: $match['metadata']['_mindwave_doc_content'],
                    metadata: array_merge($meta, [
                        '_mindwave_doc_source_id' => $match['metadata']['_mindwave_doc_source_id'] ?? null,
                        '_mindwave_doc_source_type' => $match['metadata']['_mindwave_doc_source_type'] ?? null,
                        '_mindwave_doc_chunk_index' => $match['metadata']['_mindwave_doc_chunk_index'] ?? null,
                    ]),
                ),
                score: $match['score'],
            );
        })->toArray();
    }

    public function truncate(): void
    {
        $this->client->index($this->index)->vectors()->delete(deleteAll: true);
    }

    public function itemCount(): int
    {
        return $this->client->index($this->index)->vectors()->stats()->json('totalVectorCount');
    }
}
