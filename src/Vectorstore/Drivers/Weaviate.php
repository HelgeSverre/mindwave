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

    protected int $dimensions;

    public function __construct(WeaviateClient $client, string $className, int $dimensions = 1536)
    {
        $this->client = $client;
        $this->className = $className;
        $this->dimensions = $dimensions;
    }

    protected function ensureClassExists()
    {
        if ($this->client->schema()->get()->getClasses()->isEmpty()) {
            $this->client->schema()->createClass([
                'class' => $this->className,
                'description' => 'Created by Mindwave',
                'vectorizer' => 'none',
                'properties' => [
                    ['dataType' => ['string'], 'name' => '_mindwave_doc_source_id'],
                    ['dataType' => ['string'], 'name' => '_mindwave_doc_source_type'],
                    ['dataType' => ['int'], 'name' => '_mindwave_doc_chunk_index'],
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
        $actualDimension = count($entry->vector->values);
        if ($actualDimension !== $this->dimensions) {
            throw new \InvalidArgumentException(
                "Expected vector dimension {$this->dimensions}, got {$actualDimension}"
            );
        }

        $this->ensureClassExists();

        $this->client->dataObject()->create([
            'id' => Str::uuid()->toString(),
            'class' => $this->className,
            'vector' => $entry->vector->values,
            'properties' => $entry->meta(),
        ]);
    }

    public function insertMany(array $entries): void
    {
        // Validate all vectors have correct dimensions before inserting
        foreach ($entries as $index => $entry) {
            $actualDimension = count($entry->vector->values);
            if ($actualDimension !== $this->dimensions) {
                throw new \InvalidArgumentException(
                    "Expected vector dimension {$this->dimensions}, got {$actualDimension} at index {$index}"
                );
            }
        }

        $objects = collect($entries)->map(function (VectorStoreEntry $entry) {
            return [
                'id' => Str::uuid()->toString(),
                'class' => $this->className,
                'vector' => $entry->vector->values,
                'properties' => $entry->meta(),
            ];
        })->toArray();

        $this->client->batch()->create($objects);
    }

    public function similaritySearch(EmbeddingVector $embedding, int $count = 5): array
    {

        $data = $this->client->graphql()->get("{
  Get {
    MindwaveItems(
      limit: {$count}
      nearVector: {vector: {$embedding->toJson()}}
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
}");

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
        // Silly, but lets make sure it exists first.
        $this->ensureClassExists();

        // No way to bulk delete, simply delete the entire schema and rebuild it.
        $this->client->schema()->deleteClass($this->className);

        $this->ensureClassExists();
    }

    public function itemCount(): int
    {
        $data = $this->client->graphql()->get(" { Aggregate { {$this->className}  { meta { count } } } }");

        return Arr::get($data, 'data.Aggregate.'.$this->className.'.0.meta.count');
    }

    /**
     * Get the expected vector dimensions for this collection.
     */
    public function getDimensions(): int
    {
        return $this->dimensions;
    }
}
