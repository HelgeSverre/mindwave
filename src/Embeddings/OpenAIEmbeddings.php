<?php

namespace Mindwave\Mindwave\Embeddings;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Knowledge\Data\Knowledge;
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

    public function embed(Knowledge $knowledge): EmbeddingVector
    {
        return Arr::first($this->embedInternal([$knowledge->content()]));
    }

    public function embedMultiple(array|Collection $items): array
    {
        return collect($items)
            ->map(fn (Knowledge $knowledge) => $knowledge->content())
            ->pipe(fn (Collection $collection) => $this->embedInternal($collection->toArray()));
    }

    public function embedQuery(string $text): EmbeddingVector
    {
        return Arr::first($this->embedInternal([$text]));
    }

    /**
     * @param  array<string>  $inputs
     * @return array<EmbeddingVector[]>
     */
    protected function embedInternal(array $inputs): array
    {
        $response = $this->client->embeddings()->create([
            'model' => $this->model,
            'input' => $inputs,
            // TODO(14 mai 2023) ~ Helge: add "user" key, that can be set globally using config
        ]);

        $embeddings = [];

        /** @var CreateResponseEmbedding $embedding */
        foreach ($response->embeddings as $embedding) {
            $embeddings[] = new EmbeddingVector($embedding->embedding);
        }

        return $embeddings;
    }
}
