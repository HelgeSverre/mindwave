<?php

declare(strict_types=1);

use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

describe('VectorStoreEntry', function () {
    describe('Construction', function () {
        it('creates entry with vector and document', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content');

            $entry = new VectorStoreEntry($vector, $document);

            expect($entry->vector)->toBe($vector);
            expect($entry->document)->toBe($document);
            expect($entry->score)->toBeNull();
        });

        it('creates entry with score', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content');

            $entry = new VectorStoreEntry($vector, $document, 0.95);

            expect($entry->score)->toBe(0.95);
        });

        it('accepts zero score', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content');

            $entry = new VectorStoreEntry($vector, $document, 0.0);

            expect($entry->score)->toBe(0.0);
        });

        it('accepts negative score', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content');

            $entry = new VectorStoreEntry($vector, $document, -0.5);

            expect($entry->score)->toBe(-0.5);
        });
    });

    describe('cloneWithScore', function () {
        it('creates new entry with different score', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content');
            $entry = new VectorStoreEntry($vector, $document, 0.5);

            $cloned = $entry->cloneWithScore(0.95);

            expect($cloned->score)->toBe(0.95);
            expect($cloned->vector)->toBe($vector);
            expect($cloned->document)->toBe($document);
        });

        it('does not modify original entry', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content');
            $entry = new VectorStoreEntry($vector, $document, 0.5);

            $entry->cloneWithScore(0.95);

            expect($entry->score)->toBe(0.5);
        });

        it('preserves vector reference', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content');
            $entry = new VectorStoreEntry($vector, $document);

            $cloned = $entry->cloneWithScore(0.8);

            expect($cloned->vector)->toBe($entry->vector);
        });
    });

    describe('meta()', function () {
        it('returns metadata with document content', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content');
            $entry = new VectorStoreEntry($vector, $document);

            $meta = $entry->meta();

            expect($meta['_mindwave_doc_content'])->toBe('Test content');
        });

        it('includes source id from document metadata', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Content', [
                '_mindwave_doc_source_id' => 'source-123',
            ]);
            $entry = new VectorStoreEntry($vector, $document);

            $meta = $entry->meta();

            expect($meta['_mindwave_doc_source_id'])->toBe('source-123');
        });

        it('includes source type from document metadata', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Content', [
                '_mindwave_doc_source_type' => 'file',
            ]);
            $entry = new VectorStoreEntry($vector, $document);

            $meta = $entry->meta();

            expect($meta['_mindwave_doc_source_type'])->toBe('file');
        });

        it('includes chunk index from document metadata', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Content', [
                '_mindwave_doc_chunk_index' => 5,
            ]);
            $entry = new VectorStoreEntry($vector, $document);

            $meta = $entry->meta();

            expect($meta['_mindwave_doc_chunk_index'])->toBe(5);
        });

        it('serializes document metadata as JSON', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Content', [
                'custom_key' => 'custom_value',
                'number' => 42,
            ]);
            $entry = new VectorStoreEntry($vector, $document);

            $meta = $entry->meta();
            $decoded = json_decode($meta['_mindwave_doc_metadata'], true);

            expect($decoded['custom_key'])->toBe('custom_value');
            expect($decoded['number'])->toBe(42);
        });

        it('handles empty document metadata', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Content');
            $entry = new VectorStoreEntry($vector, $document);

            $meta = $entry->meta();

            expect($meta['_mindwave_doc_source_id'])->toBeNull();
            expect($meta['_mindwave_doc_source_type'])->toBeNull();
            expect($meta['_mindwave_doc_chunk_index'])->toBeNull();
            expect($meta['_mindwave_doc_metadata'])->toBe('[]');
        });
    });
});
