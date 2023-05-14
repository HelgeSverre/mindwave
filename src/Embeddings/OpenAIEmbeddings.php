<?php

namespace Mindwave\Mindwave\Embeddings;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mindwave\Mindwave\Contracts\EmbeddingsContract;
use Mindwave\Mindwave\Knowledge\Knowledge;
use OpenAI\Client;
use OpenAI\Responses\Embeddings\CreateResponseEmbedding;

class OpenAIEmbeddings implements EmbeddingsContract
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param  array<Knowledge>|Collection<Knowledge>  $items
     * @return array<array<float>>
     */
    public function embedKnowledge(array|Collection $items): array
    {
        return collect($items)
            ->map(fn (Knowledge $knowledge) => $knowledge->content())
            ->pipe(fn (Collection $collection) => $this->embed($collection->toArray()));
    }

    /**
     * @return array<float>
     */
    public function embedQuery(string $text): array
    {
        return Arr::first($this->embed([$text]));
    }

    /**
     * @param  array<string>  $inputs
     * @return array<array<float>>
     */
    protected function embed(array $inputs): array
    {
        $response = $this->client->embeddings()->create([
            'model' => 'text-similarity-babbage-001',
            'input' => $inputs,
        ]);

        $embeddings = [];

        /** @var CreateResponseEmbedding $embedding */
        foreach ($response->embeddings as $embedding) {
            $embeddings[] = $embedding->embedding;
        }

        return $embeddings;
    }
}
