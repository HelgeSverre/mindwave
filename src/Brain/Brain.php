<?php

namespace Mindwave\Mindwave\Brain;

use Illuminate\Support\Arr;
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

        $documents = [];
        foreach ($results as $result) {
            // TODO(29 May 2023) ~ Helge: method Document::fromVectorStoreEntry($entry) ?
            $documents[] = new Document(
                content: $result->metadata['_mindwave_content'],
                metadata: [
                    '_mindwave_source_id' => $result->metadata['_mindwave_source_id'],
                    '_mindwave_source_type' => $result->metadata['_mindwave_source_type'],
                    '_mindwave_content' => $result->metadata['_mindwave_content'],
                    '_mindwave_chunk_index' => $result->metadata['_mindwave_chunk_index'],
                    '_mindwave_metadata' => $result->metadata['_mindwave_metadata'],
                ]
            );
        }

        return $documents;
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
                    '_mindwave_content' => Arr::get($doc->metadata(), '_mindwave_content'),
                    '_mindwave_chunk_index' => $chunkIndex,
                    '_mindwave_metadata' => $doc->metadata(),
                ],
            );
        }

        $this->vectorstore->insertVectors($entries);

        return $this;
    }
}
