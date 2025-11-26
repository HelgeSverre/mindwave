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
     * Validate that all entries have the correct embedding dimensions for the vectorstore.
     *
     * @param  VectorStoreEntry[]  $entries
     *
     * @throws \InvalidArgumentException  If any entry has mismatched dimensions
     */
    private function validateDimensions(array $entries): void
    {
        // Only validate if the vectorstore exposes dimensions
        if (! method_exists($this->vectorstore, 'getDimensions')) {
            return;
        }

        $expected = $this->vectorstore->getDimensions();

        foreach ($entries as $index => $entry) {
            $actual = count($entry->vector->values);
            if ($actual !== $expected) {
                throw new \InvalidArgumentException(
                    "Embedding dimension mismatch at index {$index}: expected {$expected}, got {$actual}. " .
                    'Ensure your embedding model dimensions match your vector store configuration.'
                );
            }
        }
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

        // Validate dimensions before inserting
        $this->validateDimensions($entries);

        // Insert all entries in a single batch operation
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

        // Validate dimensions before inserting
        $this->validateDimensions($entries);

        $this->vectorstore->insertMany($entries);

        return $this;
    }
}
