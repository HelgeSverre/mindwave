<?php

namespace Mindwave\Mindwave\Vectorstore\Data;

use Illuminate\Support\Arr;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;

class VectorStoreEntry
{
    public readonly EmbeddingVector $vector;

    public readonly Document $document;

    public readonly ?float $score;

    public function __construct(EmbeddingVector $vector, Document $document, ?float $score = null)
    {
        $this->vector = $vector;
        $this->score = $score;
        $this->document = $document;
    }

    public function cloneWithScore(float $score): self
    {
        return new self(
            vector: $this->vector,
            document: $this->document,
            score: $score,
        );
    }

    public function meta(): array
    {
        return [
            '_mindwave_doc_source_id' => Arr::get($this->document->metadata(), '_mindwave_doc_source_id'),
            '_mindwave_doc_source_type' => Arr::get($this->document->metadata(), '_mindwave_doc_source_type'),
            '_mindwave_doc_chunk_index' => Arr::get($this->document->metadata(), '_mindwave_doc_chunk_index'),
            '_mindwave_doc_content' => $this->document->content(),
            '_mindwave_doc_metadata' => json_encode($this->document->metadata()),
        ];
    }
}
