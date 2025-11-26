<?php

use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\TextSplitters\RecursiveCharacterTextSplitter;
use Mindwave\Mindwave\TextSplitters\TextSplitter;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

describe('Brain', function () {
    describe('Constructor', function () {
        it('initializes with default RecursiveCharacterTextSplitter', function () {
            $vectorstore = Mockery::mock(Vectorstore::class);
            $embeddings = Mockery::mock(Embeddings::class);

            $brain = new Brain($vectorstore, $embeddings);

            // Brain should be created without errors
            expect($brain)->toBeInstanceOf(Brain::class);
        });

        it('accepts custom text splitter', function () {
            $vectorstore = Mockery::mock(Vectorstore::class);
            $embeddings = Mockery::mock(Embeddings::class);
            $textSplitter = Mockery::mock(TextSplitter::class);

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);

            expect($brain)->toBeInstanceOf(Brain::class);
        });
    });

    describe('search()', function () {
        it('searches vectorstore with embedded query', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->once()
                ->with('test query')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->once()
                ->with($embedding, 5)
                ->andReturn([]);

            $brain = new Brain($vectorstore, $embeddings);
            $results = $brain->search('test query');

            expect($results)->toBe([]);
        });

        it('returns array of Document objects from search results', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test content', ['source' => 'test']);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->andReturn([
                    new VectorStoreEntry($embedding, $document, 0.95),
                ]);

            $brain = new Brain($vectorstore, $embeddings);
            $results = $brain->search('query');

            expect($results)->toBeArray();
            expect($results)->toHaveCount(1);
            expect($results[0])->toBeInstanceOf(Document::class);
            expect($results[0]->content())->toBe('Test content');
        });

        it('respects count parameter for search limit', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->once()
                ->with($embedding, 10)
                ->andReturn([]);

            $brain = new Brain($vectorstore, $embeddings);
            $brain->search('query', 10);
        });
    });

    describe('consume()', function () {
        it('splits document into chunks using text splitter', function () {
            $document = new Document('This is a test document with some content.');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->once()
                ->with($document)
                ->andReturn([$document]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocument')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('insertMany');

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            $brain->consume($document);
        });

        it('embeds each chunk', function () {
            $document = new Document('Test content');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->andReturn([$document, $document]); // 2 chunks

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocument')
                ->twice()
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('insertMany');

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            $brain->consume($document);
        });

        it('adds chunk index to document metadata', function () {
            $document = new Document('Test content');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->andReturn([$document]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocument')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('insertMany')
                ->once()
                ->with(Mockery::on(function ($entries) {
                    return count($entries) === 1
                        && $entries[0]->document->metadata()['_mindwave_doc_chunk_index'] === 0;
                }));

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            $brain->consume($document);
        });

        it('returns self for method chaining', function () {
            $document = new Document('Test content');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->andReturn([$document]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocument')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('insertMany');

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            $result = $brain->consume($document);

            expect($result)->toBe($brain);
        });
    });

    describe('consumeAll()', function () {
        it('processes multiple documents in batch', function () {
            $doc1 = new Document('Document 1');
            $doc2 = new Document('Document 2');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->twice()
                ->andReturn([new Document('chunk')]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocuments')
                ->once()
                ->andReturn([$embedding, $embedding]);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('insertMany');

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            $brain->consumeAll([$doc1, $doc2]);
        });

        it('batch embeds all chunks for performance', function () {
            $doc = new Document('Test');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->andReturn([new Document('c1'), new Document('c2'), new Document('c3')]);

            $embeddings = Mockery::mock(Embeddings::class);
            // embedDocuments should be called once with all chunks
            $embeddings->shouldReceive('embedDocuments')
                ->once()
                ->with(Mockery::on(fn ($docs) => count($docs) === 3))
                ->andReturn([$embedding, $embedding, $embedding]);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('insertMany');

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            $brain->consumeAll([$doc]);
        });

        it('returns self for method chaining', function () {
            $doc = new Document('Test');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->andReturn([new Document('chunk')]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocuments')
                ->andReturn([$embedding]);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('insertMany');

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            $result = $brain->consumeAll([$doc]);

            expect($result)->toBe($brain);
        });
    });

    describe('Dimension Validation', function () {
        it('validates dimensions when vectorstore has getDimensions()', function () {
            // Use a concrete test class that has getDimensions
            $doc = new Document('Test');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]); // 3 dimensions

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->andReturn([$doc]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocument')
                ->andReturn($embedding);

            // Create an anonymous class that implements Vectorstore AND has getDimensions
            $vectorstore = new class implements Vectorstore {
                public function truncate(): void {}
                public function itemCount(): int { return 0; }
                public function insert(\Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry $entry): void {}
                public function insertMany(array $entries): void {
                    throw new \RuntimeException('Should not reach insertMany due to dimension mismatch');
                }
                public function similaritySearch(\Mindwave\Mindwave\Embeddings\Data\EmbeddingVector $embedding, int $count = 5): array { return []; }
                public function getDimensions(): int { return 5; } // Mismatch with 3-dim embedding
            };

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);

            expect(fn () => $brain->consume($doc))
                ->toThrow(InvalidArgumentException::class, 'Embedding dimension mismatch');
        });

        it('passes validation when dimensions match', function () {
            $doc = new Document('Test');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]); // 3 dimensions

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->andReturn([$doc]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocument')
                ->andReturn($embedding);

            $insertCalled = false;
            $vectorstore = new class($insertCalled) implements Vectorstore {
                public function __construct(private bool &$insertCalled) {}
                public function truncate(): void {}
                public function itemCount(): int { return 0; }
                public function insert(\Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry $entry): void {}
                public function insertMany(array $entries): void { $this->insertCalled = true; }
                public function similaritySearch(\Mindwave\Mindwave\Embeddings\Data\EmbeddingVector $embedding, int $count = 5): array { return []; }
                public function getDimensions(): int { return 3; } // Matches 3-dim embedding
            };

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            $brain->consume($doc);

            expect($insertCalled)->toBeTrue();
        });

        it('skips validation when vectorstore lacks getDimensions()', function () {
            $doc = new Document('Test');
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $textSplitter = Mockery::mock(TextSplitter::class);
            $textSplitter->shouldReceive('splitDocument')
                ->andReturn([$doc]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedDocument')
                ->andReturn($embedding);

            // Basic vectorstore mock without getDimensions
            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('insertMany')
                ->once();

            $brain = new Brain($vectorstore, $embeddings, $textSplitter);
            // Should not throw
            $brain->consume($doc);
        });
    });
});
