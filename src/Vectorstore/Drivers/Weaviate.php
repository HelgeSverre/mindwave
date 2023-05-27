<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Weaviate\Model\ClassModel;
use Weaviate\Weaviate as WeaviateClient;

class Weaviate implements Vectorstore
{
    protected WeaviateClient $client;

    private string $className;

    public function __construct(WeaviateClient $client, string $className)
    {
        $this->client = $client;
        $this->className = $className;
    }

    public function fetchById(string $id): ?VectorStoreEntry
    {
        // TODO: Implement fetchById() method.

        return null;
    }

    protected function ensureClassExists()
    {
        if ($this->client->schema()->get()->getClasses()->isEmpty()) {
            $this->client->schema()->create([
                'class' => $this->className,
                'description' => 'Created by Mindwave',
                'vectorizer' => 'none',
                'properties' => [
                    [
                        'dataType' => ['string'],
                        'description' => 'Mindwave Document ID',
                        'name' => 'mindwaveDocumentId',
                    ],
                    [
                        'dataType' => ['string'],
                        'description' => 'Mindwave Document Chunk',
                        'name' => 'mindwaveDocumentChunk',
                    ], [
                        'dataType' => ['text'],
                        'description' => 'Mindwave Document Content',
                        'name' => 'mindwaveDocumentContent',
                    ],
                ],
            ]);
        }

        $found = $this->client->schema()->get()->getClasses()->first(
            callback: fn (ClassModel $classModel) => $classModel->getClass() === $this->className,
            default: false
        );

        if (! $found) {
            throw new \Exception("Could not create Class '{$this->className}' in Weaviate");
        }

        // TODO(24 May 2023) ~ Helge: return true?
    }

    public function fetchByIds(array $ids): array
    {
        // TODO: Implement fetchByIds() method.
    }

    public function insertVector(VectorStoreEntry $entry): void
    {
        $this->ensureClassExists();

        $this->client->objects()->create([
            'id' => Str::uuid()->toString(),
            'class' => $this->className,
            'vector' => $entry->vector->values,
            'properties' => [
                'mindwaveDocumentId' => $entry->id,
                'mindwaveDocumentChunk' => $entry->metadata['_mindwave_content'],
                'mindwaveDocumentContent' => $entry->metadata['_mindwave_chunk_index'],
            ],
        ]);
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

        $json = json_encode($embedding->toArray());

        $query = <<<GRAPHQL
{
    Get {
      {$this->className} (
       limit: 2
        nearVector: {
            vector: {$json}
        }
      ) {
        mindwaveDocumentId
        mindwaveDocumentChunk
        mindwaveDocumentContent
      }
    }
}
GRAPHQL;
        //        dd($query);

        $data = $this->client->graphql()->get($query);

        $items = Arr::get($data, "data.Get.{$this->className}");

        $results = [];
        foreach ($items as $item) {
            $results[] = new VectorStoreEntry(
                id: $item['mindwaveDocumentId'],
            );
        }
        // TODO: Implement similaritySearchByVector() method.
    }
}
