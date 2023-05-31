<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Illuminate\Support\Arr;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Qdrant\Config;
use Qdrant\Http\GuzzleClient;
use Qdrant\Models\PointsStruct;
use Qdrant\Models\PointStruct;
use Qdrant\Models\Request\CreateCollection;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\Request\VectorParams;
use Qdrant\Models\VectorStruct;
use Qdrant\Qdrant as QdrantClient;

class Qdrant implements Vectorstore
{
    protected QdrantClient $client;

    protected string $collection;

    protected string $vectorsName = 'items'; // TODO(01 Jun 2023) ~ Helge: make configurable

    public function __construct(string $apiKey, string $collection, string $host, int $port = 6333)
    {
        $config = new Config($host, $port);
        $config->setApiKey($apiKey); // TODO(01 Jun 2023) ~ Helge: no way to set an api key in qdrant yet though...?

        $client = new QdrantClient(new GuzzleClient($config));

        $this->client = $client;
        $this->collection = $collection;
    }

    public function truncate(): void
    {
        $this->ensureCollectionExists();
        $this->client->collections()->delete($this->collection);
        $this->ensureCollectionExists();
    }

    protected function ensureCollectionExists()
    {
        $wip = $this->client->collections()->info($this->collection);

        if (! Arr::get($wip, 'result')) {
            $createCollection = new CreateCollection();
            $createCollection->addVector(new VectorParams(1536, VectorParams::DISTANCE_COSINE), $this->vectorsName);
            $this->client->collections()->create($this->collection, $createCollection);

            // TODO(01 Jun 2023) ~ Helge: Handle failure
        }
    }

    public function itemCount(): int
    {
        $this->ensureCollectionExists();

        $response = $this->client->collections()->info($this->collection);

        return Arr::get($response, 'result.vectors_count');
    }

    public function insert(VectorStoreEntry $entry): void
    {
        $this->ensureCollectionExists();

        // TODO(01 Jun 2023) ~ Helge: DANGER: This is a NAIVE and UNSAFE workaround, until we get UUID support in the qdrant library
        $count = $this->itemCount();

        $points = new PointsStruct();
        $points->addPoint(

            new PointStruct(
                id: ++$count, // TODO(01 Jun 2023) ~ Helge: change this to uuid string when lib supports it
                vector: new VectorStruct($entry->vector->values, $this->vectorsName),
                payload: $entry->meta()
            )
        );

        $this->client->collections($this->collection)->points()->upsert($points);
    }

    public function insertMany(array $entries): void
    {
        $this->ensureCollectionExists();

        $points = new PointsStruct();

        // TODO(01 Jun 2023) ~ Helge: DANGER: This is a NAIVE and UNSAFE workaround, until we get UUID support in the qdrant library
        $count = $this->itemCount();

        foreach ($entries as $entry) {
            $points->addPoint(
                new PointStruct(
                    id: ++$count, // TODO(01 Jun 2023) ~ Helge: change this to uuid string when lib supports it
                    vector: new VectorStruct($entry->vector->values, $this->vectorsName),
                    payload: $entry->meta()
                )
            );
        }

        $this->client->collections($this->collection)->points()->upsert($points);
    }

    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array
    {
        $this->ensureCollectionExists();

        $search = new SearchRequest(
            new VectorStruct(
                vector: $embedding->values,
                name: $this->vectorsName
            )
        );

        $search
            ->setLimit($count)
            ->setWithVector(true)
            ->setWithPayload(true);

        $response = $this->client->collections($this->collection)->points()->search($search);

        $results = Arr::get($response, 'result');

        $items = [];
        foreach ($results as $result) {
            $items[] = new VectorStoreEntry(
                vector: new EmbeddingVector($result['vector']['items']),
                document: new Document(
                    content: $result['payload']['_mindwave_doc_content'],
                    metadata: $result['payload'],
                ),
                score: $result['score']
            );
        }

        return $items;
    }
}
