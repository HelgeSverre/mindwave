<?php

namespace Mindwave\Mindwave\Brain;

use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\TextSplitters\RecursiveCharacterTextSplitter;
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

    /**
     * @return Document[]
     */
    public function search(string $query, int $count = 5): array
    {
        $results = $this->vectorstore->similaritySearchByVector(
            embedding: $this->embeddings->embedQuery($query),
            count: $count,
        );

        return $results;

        // TODO(18 mai 2023) ~ Helge: unsure what we should do here yet...
        $docs = [];

        dump($results);
        foreach ($results as $result) {

        }

        return $docs;
    }

    public function consume(Document $document): self
    {
        $splitter = new RecursiveCharacterTextSplitter();

        $docs = $splitter->splitDocument($document);

        $entries = [];

        foreach ($docs as $chunkIndex => $doc) {

            $entries[] = new VectorStoreEntry(
                id: $doc->getMetaValue('id', Str::uuid()),
                vector: $this->embeddings->embed($doc),
                metadata: [
                    '_mindwave_content' => $doc->content(),
                    '_mindwave_chunk_index' => $chunkIndex,
                    'metadata' => $doc->metadata(),
                ],
            );
        }

        $this->vectorstore->upsertVectors($entries);

        return $this;
    }
}
