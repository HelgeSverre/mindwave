<?php

namespace Mindwave\Mindwave\Brain;

use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\TextSplitters\RecursiveCharacterTextSplitter;
use Mindwave\Mindwave\TextSplitters\TextSplitter;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

class Brain
{
    public function __construct(
        protected Vectorstore $vectorstore,
        protected Embeddings $embeddings,
        protected TextSplitter $textSplitter = new RecursiveCharacterTextSplitter()
    ) {

    }

    /**
     * @return Document[]
     */
    public function search(string $query, int $count = 5): array
    {
        $results = $this->vectorstore->similaritySearch(
            embedding: $this->embeddings->embedText($query),
            count: $count,
        );

        $documents = [];
        foreach ($results as $result) {
            $documents[] = $result->document;
        }

        return $documents;
    }

    public function consumeAll(array $documents): self
    {
        // TODO: Horribly inefficient, use embedDocuments to speed up
        foreach ($documents as $document) {
            $this->consume($document);
        }

        return $this;
    }

    public function consume(Document $document): self
    {
        $docs = $this->textSplitter->splitDocument($document);

        $entries = [];

        foreach ($docs as $chunkIndex => $doc) {
            $entries[] = new VectorStoreEntry(
                vector: $this->embeddings->embedDocument($doc),
                document: new Document(
                    content: $doc->content(),
                    metadata: array_merge(
                        $doc->metadata(), [
                            '_mindwave_doc_chunk_index' => $chunkIndex,
                        ])
                )
            );
        }

        $this->vectorstore->insertMany($entries);

        return $this;
    }
}
