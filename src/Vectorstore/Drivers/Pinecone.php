<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use GuzzleHttp\Psr7\Query;
use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Probots\Pinecone\Client as PineconeClient;
use Probots\Pinecone\Requests\Exceptions\MissingNameException;

class Pinecone implements Vectorstore
{
    protected PineconeClient $client;

    private string $index;

    public function __construct(PineconeClient $client, string $index)
    {
        $this->client = $client;
        $this->index = $index;
    }

    public function fetchById(string $id): ?VectorStoreEntry
    {
        $result = $this->client->index($this->index)->vectors()->fetch([$id])->collect('vectors')->first();

        if (! $result) {
            return null;
        }

        return new VectorStoreEntry(
            id: $result['id'],
            vector: new EmbeddingVector($result['values']),
            metadata: $result['metadata'] ?? []
        );

    }

    public function fetchByIds(array $ids): array
    {

        $host = $this->client->index($this->index)->describe()->json('status.host');

        $params = Query::build(['ids' => $ids]);

        /**
         * @todo remove this when issue in pinecone-php has been fixed
         *
         * @see https://github.com/probots-io/pinecone-php/issues/3
         */
        return Http::withHeaders([
            'Api-Key' => $this->client->apiKey,
        ])
            ->acceptJson()
            ->asJson()
            ->get("https://{$host}/vectors/fetch?{$params}")
            ->collect('vectors')
            ->values()
            ->map(fn ($result) => new VectorStoreEntry(
                id: $result['id'],
                vector: new EmbeddingVector($result['values']),
                metadata: $result['metadata'] ?? []
            ))
            ->toArray();

        // TODO(22 May 2023) ~ Helge: Replace with this
        // return $this->client->index($this->index)->vectors()
        //     ->fetch($ids)
        //     ->collect('vectors')
        //     ->map(fn($result) => new VectorStoreEntry(
        //         id: $result['id'],
        //         vector: new EmbeddingVector($result['values']),
        //         metadata: $result['metadata'] ?? []
        //     ))
        //     ->toArray();
    }

    public function insertVector(VectorStoreEntry $entry): void
    {
        $vectors = array_filter([
            'id' => $entry->id,
            'values' => $entry->vector->values,
            'metadata' => $entry->metadata,
        ]);

        $this->client->index($this->index)->vectors()->upsert($vectors);
    }

    public function upsertVector(VectorStoreEntry $entry): void
    {
        $this->insertVector($entry);
    }

    /**
     * @param  VectorStoreEntry[]  $entries
     *
     * @throws MissingNameException
     */
    public function insertVectors(array $entries): void
    {
        $vectors = collect($entries)->map(fn (VectorStoreEntry $entry) => array_filter([
            'id' => $entry->id,
            'values' => $entry->vector->values,
            'metadata' => $entry->metadata,
        ]))->toArray();

        $this->client->index($this->index)->vectors()->upsert($vectors);
    }

    /**
     * @param  VectorStoreEntry[]  $entries
     *
     * @throws MissingNameException
     */
    public function upsertVectors(array $entries): void
    {
        $this->insertVectors($entries);
    }

    /**
     * @return VectorStoreEntry[] $entries
     */
    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array
    {
        return $this->client->index($this->index)->vectors()->query(
            vector: $embedding->values,
            topK: $count,
            includeMetadata: true,
            includeVector: true,
        )->collect('results')->map(fn ($result) => new VectorStoreEntry(
            id: $result['id'],
            vector: new EmbeddingVector($result['values']),
            metadata: $result['metadata'] ?? [],
            score: $result['score'],
        ))->toArray();
    }
}
