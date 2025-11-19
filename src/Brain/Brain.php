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
        protected TextSplitter $textSplitter = new RecursiveCharacterTextSplitter
    ) {}

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
        // Split all documents into chunks first
        $allChunks = [];
        $chunkMetadata = [];

        foreach ($documents as $document) {
            $chunks = $this->textSplitter->splitDocument($document);

            foreach ($chunks as $chunkIndex => $chunk) {
                $allChunks[] = $chunk;
                $chunkMetadata[] = [
                    'chunk' => $chunk,
                    'chunkIndex' => $chunkIndex,
                ];
            }
        }

        // Batch embed all chunks at once for better performance
        $embeddings = $this->embeddings->embedDocuments($allChunks);

        // Build vector store entries with the embeddings
        $entries = [];
        foreach ($embeddings as $index => $embedding) {
            $metadata = $chunkMetadata[$index];
            $entries[] = new VectorStoreEntry(
                vector: $embedding,
                document: new Document(
                    content: $metadata['chunk']->content(),
                    metadata: array_merge(
                        $metadata['chunk']->metadata(),
                        ['_mindwave_doc_chunk_index' => $metadata['chunkIndex']]
                    )
                )
            );
        }

        // Insert all entries in a single batch operation
        // TODO: Validate all embedding dimensions match vector store dimensions
        // Should throw exception if any embedding dimension != $vectorstore->dimensions
        $this->vectorstore->insertMany($entries);

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

        // TODO: Validate embedding dimensions match vector store dimensions
        // Should throw exception if $entry->vector->count() != $vectorstore->dimensions
        $this->vectorstore->insertMany($entries);

        return $this;
    }
}
