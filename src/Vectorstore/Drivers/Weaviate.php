<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Weaviate\Model\ClassModel;
use Weaviate\Weaviate as WeaviateClient;

class Weaviate implements Vectorstore
{
    protected WeaviateClient $client;

    protected string $className;

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
                    ['dataType' => ['string'], 'name' => '_mindwave_doc_source_id'],
                    ['dataType' => ['string'], 'name' => '_mindwave_doc_source_type'],
                    ['dataType' => ['string'], 'name' => '_mindwave_doc_chunk_index'],
                    ['dataType' => ['text'], 'name' => '_mindwave_doc_content'],
                    ['dataType' => ['text'], 'name' => '_mindwave_doc_metadata'],
                ],
            ]);
        }

        $found = $this->client->schema()->get()->getClasses()->first(
            callback: fn (ClassModel $classModel) => $classModel->getClass() === $this->className,
            default: false
        );

        if (! $found) {
            throw new Exception("Could not create Class '{$this->className}' in Weaviate");
        }

    }

    public function insert(VectorStoreEntry $entry): void
    {
        $this->ensureClassExists();

        $this->client->objects()->create([
            'id' => Str::uuid()->toString(),
            'class' => $this->className,
            'vector' => $entry->vector->values,
            'properties' => $entry->meta(),
        ]);
    }

    public function insertMany(array $entries): void
    {
        // TODO: Implement insertMany() method.
    }

    public function similaritySearchByVector(EmbeddingVector $embedding, int $count = 5): array
    {

        $json = json_encode($embedding->toArray());

        $query = <<<GRAPHQL
{
  Get {
    MindwaveItems(
      limit: {$count}
      nearVector: {vector: {$json}}
    ) {
      _additional {
        vector
        score
      }
      _mindwave_doc_source_id
      _mindwave_doc_source_type
      _mindwave_doc_chunk_index
      _mindwave_doc_content
      _mindwave_doc_metadata
    }
  }
}

GRAPHQL;

        $data = $this->client->graphql()->get($query);

        $items = Arr::get($data, "data.Get.{$this->className}");

        $results = [];
        foreach ($items as $item) {
            $meta = json_decode($item['_mindwave_doc_metadata'], true);

            $results[] = new VectorStoreEntry(
                vector: new EmbeddingVector($item['_additional']['vector']),
                document: new Document(
                    content: $item['_mindwave_doc_content'],
                    metadata: array_merge($meta, [
                        '_mindwave_doc_source_id' => $item['_mindwave_doc_source_id'],
                        '_mindwave_doc_source_type' => $item['_mindwave_doc_source_type'],
                        '_mindwave_doc_chunk_index' => $item['_mindwave_doc_chunk_index'],

                    ])
                ),
                score: $item['_additional']['score']
            );
        }

        return $results;
    }

    public function truncate(): void
    {
        // No way to bulk delete, simply delete the entire schema and rebuild it.
        $this->client->schema()->delete($this->className);
        $this->ensureClassExists();
    }

    public function itemCount(): int
    {
        $data = $this->client->graphql()->get(" { Aggregate { {$this->className}  { meta { count } } } }");

        return Arr::get($data, 'data.Aggregate.'.$this->className.'.0.meta.count');
    }
}
