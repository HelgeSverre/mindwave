<?php

namespace Mindwave\Mindwave\Brain;

use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Knowledge\Data\Document;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

class Brain
{
    protected Vectorstore $vectorstore;

    protected Embeddings $embeddings;

    public function __construct(Vectorstore $vectorstore, Embeddings $embeddings)
    {
        $this->vectorstore = $vectorstore;
        $this->embeddings = $embeddings;
    }

    public function search(string $query, int $count = 5): array
    {
        $results = $this->vectorstore->similaritySearchByVector(
            embedding: $this->embeddings->embedQuery($query),
            count: $count,
        );

        $docs = [];

        foreach ($results as $result) {

        }

    }

    public function consume(Document $knowledge): self
    {
        // TODO(14 mai 2023) ~ Helge: Text splitter

        $this->vectorstore->upsertVector(new VectorStoreEntry(
            id: $knowledge->getMetaValue('id', Str::uuid()),
            vector: $this->embeddings->embed($knowledge),
            metadata: $knowledge->toArray(),
        ));

        return $this;
    }
}
