<?php

namespace Mindwave\Mindwave\Embeddings\Drivers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use OpenAI\Client;
use OpenAI\Responses\Embeddings\CreateResponseEmbedding;

class OpenAIEmbeddings implements Embeddings
{
    protected Client $client;

    protected string $model;

    public function __construct(Client $client, string $model = 'text-embedding-ada-002')
    {
        $this->client = $client;
        $this->model = $model;
    }

    public function embedText(string $text): EmbeddingVector
    {
        return Arr::first($this->embedTexts([$text]));
    }

    public function embedTexts(array $texts): array
    {
        $response = $this->client->embeddings()->create([
            'model' => $this->model,
            'input' => $texts,
        ]);

        $embeddings = [];

        /** @var CreateResponseEmbedding $embedding */
        foreach ($response->embeddings as $embedding) {
            $embeddings[] = new EmbeddingVector($embedding->embedding);
        }

        return $embeddings;
    }

    public function embedDocument(Document $document): EmbeddingVector
    {
        return Arr::first($this->embedTexts([$document->content()]));
    }

    public function embedDocuments(array|Collection $items): array
    {
        return collect($items)
            ->map(fn (Document $document) => $document->content())
            ->pipe(fn (Collection $collection) => $this->embedTexts($collection->toArray()));
    }
}
