<?php

namespace Mindwave\Mindwave\Brain;

use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\TextSplitters\RecursiveCharacterTextSplitter;
use Mindwave\Mindwave\TextSplitters\TextSplitter;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

class Brain
{
    protected Vectorstore $vectorstore;

    protected Embeddings $embeddings;

    protected TextSplitter $textSplitter;

    public function __construct(Vectorstore $vectorstore, Embeddings $embeddings, ?TextSplitter $textSplitter = null)
    {
        $this->vectorstore = $vectorstore;
        $this->embeddings = $embeddings;
        $this->textSplitter = $textSplitter ?? new RecursiveCharacterTextSplitter();
    }

    /**
     * @return Document[]
     */
    public function search(string $query, int $count = 5): array
    {
        $results = $this->vectorstore->similaritySearchByVector(
            embedding: $this->embeddings->embedText($query),
            count: $count,
        );

        // TODO(27 May 2023) ~ Helge: Convert back to documents

        return $results;
    }

    public function consume(Document $document): self
    {
        $docs = $this->textSplitter->splitDocument($document);

        $entries = [];

        foreach ($docs as $chunkIndex => $doc) {

            $entries[] = new VectorStoreEntry(
                id: Str::uuid()->toString(),
                vector: $this->embeddings->embedDocument($doc),
                metadata: [
                    '_mindwave_source_id' => Arr::get($doc->metadata(), '_mindwave_source_id'),
                    '_mindwave_source_type' => Arr::get($doc->metadata(), '_mindwave_source_type'),
                    '_mindwave_chunk_index' => $chunkIndex,
                    '_mindwave_metadata' => $doc->metadata(),
                ],
            );
        }

        $this->vectorstore->insertVectors($entries);

        return $this;
    }
}
